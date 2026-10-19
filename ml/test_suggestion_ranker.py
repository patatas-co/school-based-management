import json
import unittest
from pathlib import Path
from types import SimpleNamespace
from unittest.mock import Mock, patch

import numpy as np

from recommendation_engine import (
    _build_prompt,
    _call_groq,
    _format_dimension_suggestions,
    _parse_dimension_suggestions,
    compute_recommendation_confidence,
    generate_ip_field,
    generate_recommendations,
)
from suggestion_ranker import (
    CATEGORICAL_FEATURES,
    DEMO_MODEL_PATH,
    _frame,
    _new_model,
    _cycle_group_splits,
    _records_from_bridge,
    rank_suggestions,
    suggestion_features,
)


class SuggestionRankerTests(unittest.TestCase):
    def test_output_formats_precomputed_comparison_and_dimension_sections(self):
        text = _format_dimension_suggestions(
            [
                {
                    "dimension_id": 2,
                    "title": "D2 Learning Environment",
                    "text": "- First action.\n- Second action.\n- Third action.",
                },
            ],
            {
                "overview_text": (
                    "The current school year overall SBM score is 89.3%, compared "
                    "with 85.0% in the previous school year (+4.3 percentage points). "
                    "Biggest decline: D1 Curriculum (-7.2 percentage points). "
                    "Biggest improvement: D2 Learning Environment (+5.0 percentage points)."
                ),
                "dimensions": {
                    "2": {
                        "current_score": 91.3,
                        "change": -7.2,
                    },
                },
                "weakest_indicators": {
                    "2": [
                        {"code": "2.1", "rating": 1.8},
                        {"code": "2.2", "rating": 2.1},
                    ],
                },
            },
        )

        self.assertTrue(text.startswith("The current school year overall SBM score"))
        self.assertEqual(text.count("**D2 Learning Environment**"), 1)
        self.assertIn("91.3% · −7.2 pts vs last year", text)
        self.assertIn("Weakest indicators: 2.1 (avg 1.8), 2.2 (avg 2.1)", text)
        self.assertEqual(sum(line.startswith("- ") for line in text.splitlines()), 3)

    def test_no_prior_year_output_uses_exact_fallback_text(self):
        text = _format_dimension_suggestions(
            [{
                "dimension_id": 1,
                "title": "D1 Curriculum",
                "text": "- One.\n- Two.\n- Three.",
            }],
            {
                "overview_text": "No prior-year data available.",
                "dimensions": {},
            },
        )

        self.assertTrue(text.startswith("No prior-year data available."))
        self.assertIn("N/A · No prior-year data available", text)
        self.assertIn("Weakest indicators: No rated indicators available", text)

    def test_prompt_restricts_model_to_three_actions_and_provided_metrics(self):
        prompt = _build_prompt(
            {
                "gap_analysis": {"all_dimensions": [{
                    "dimension_id": 1,
                    "dimension_no": 1,
                    "dimension_name": "Curriculum",
                    "score": 80.0,
                }]},
                "score_comparison": {
                    "overview_text": "D1 declined by 2.0 percentage points.",
                    "dimensions": {"1": {"current_score": 80, "change": -2}},
                },
            },
            "Sample School",
            "2026-2027",
        )

        self.assertIn('"actions": ["Action one.", "Action two.", "Action three."]', prompt)
        self.assertIn("Never calculate, derive, round, or invent any scores", prompt)
        self.assertIn("do not return headings, titles, scores, deltas", prompt)
        self.assertIn("supplied indicator code in every action", prompt)
        self.assertIn("Lowest-rated indicators by dimension", prompt)
        self.assertIn("Use relative deadlines only", prompt)
        self.assertIn("Never use calendar dates or years in action bullets", prompt)

    def test_parser_flags_calendar_years_in_action_bullets(self):
        dimensions = [{
            "dimension_id": 1,
            "dimension_no": 1,
            "dimension_name": "Curriculum",
        }]
        generated = {
            "suggestions": [{
                "dimension_id": 1,
                "actions": [
                    "The School Head will complete the review by 30 June 2023.",
                    "The coordinator will check progress every month.",
                    "The team will report at the end of each quarter.",
                ],
            }]
        }

        result = _parse_dimension_suggestions(
            json.dumps(generated), dimensions
        )[0]

        self.assertTrue(result["calendar_year_violation"])
        self.assertTrue(result["needs_review"])
        self.assertIn("4-digit years", result["review_reason"])

    def test_generation_retries_calendar_years_and_uses_clean_actions(self):
        dimensions = [{
            "dimension_id": 1,
            "dimension_no": 1,
            "dimension_name": "Curriculum",
            "score": 80,
        }]
        dated = {
            "suggestions": [{
                "dimension_id": 1,
                "actions": [
                    "The School Head will review indicator 1.2 by 30 June 2023.",
                    "The coordinator will check indicator 1.2 every month.",
                    "The team will report indicator 1.2 each quarter.",
                ],
            }]
        }
        relative = {
            "suggestions": [{
                "dimension_id": 1,
                "actions": [
                    "The School Head will review indicator 1.2 within 30 days.",
                    "The coordinator will check indicator 1.2 every month.",
                    "The team will report indicator 1.2 at the end of each quarter.",
                ],
            }]
        }

        with (
            patch(
                "recommendation_engine._call_groq",
                side_effect=[json.dumps(dated), json.dumps(relative)],
            ) as call_groq,
            patch("suggestion_ranker.MODEL_PATH", Path("missing_ranker.joblib")),
        ):
            response = generate_recommendations(
                {
                    "gap_analysis": {"all_dimensions": dimensions},
                    "dimension_rows": dimensions,
                    "dimension_weakest_indicators": {
                        "1": [{"code": "1.2", "rating": 1.8}],
                    },
                },
                "Sample School",
                "2026-2027",
            )

        self.assertEqual(call_groq.call_count, 2)
        self.assertNotIn("2023", response["recommendations"])
        self.assertIn("within 30 days", response["recommendations"])
        self.assertFalse(response["blocks"][0]["needs_review"])

    def test_parser_requires_actions_to_cite_dimension_indicators(self):
        dimensions = [{
            "dimension_id": 1,
            "dimension_no": 1,
            "dimension_name": "Curriculum",
        }]
        weakest = {"1": [
            {"code": "1.2", "rating": 1.8},
            {"code": "1.3", "rating": 2.0},
        ]}
        uncited = {
            "suggestions": [{
                "dimension_id": 1,
                "actions": ["First action.", "Second action.", "Third action."],
            }]
        }
        cited = {
            "suggestions": [{
                "dimension_id": 1,
                "actions": [
                    "Review indicator 1.2 monthly.",
                    "Track indicator 1.3 by June.",
                    "Report indicator 1.2 quarterly.",
                ],
            }]
        }

        uncited_result = _parse_dimension_suggestions(
            json.dumps(uncited), dimensions, weakest_indicators_by_dimension=weakest
        )[0]
        cited_result = _parse_dimension_suggestions(
            json.dumps(cited), dimensions, weakest_indicators_by_dimension=weakest
        )[0]

        self.assertFalse(uncited_result["actions_supported"])
        self.assertTrue(uncited_result["needs_review"])
        self.assertTrue(cited_result["actions_supported"])
        self.assertFalse(cited_result["needs_review"])

    def test_generation_sorts_dimensions_by_precomputed_decline(self):
        dimensions = [
            {
                "dimension_id": 1,
                "dimension_no": 1,
                "dimension_name": "Curriculum",
                "score": 80,
            },
            {
                "dimension_id": 2,
                "dimension_no": 2,
                "dimension_name": "Learning Environment",
                "score": 70,
            },
        ]
        generated = {
            "suggestions": [
                {"dimension_id": 1, "actions": ["Action one.", "Action two.", "Action three."]},
                {"dimension_id": 2, "actions": ["Action one.", "Action two.", "Action three."]},
            ]
        }
        comparison = {
            "overview_text": "Overview from precomputed scores.",
            "dimensions": {
                "1": {"current_score": 80, "change": 2.0, "change_text": "+2.0 pp", "trend_text": "Improved."},
                "2": {"current_score": 70, "change": -4.0, "change_text": "−4.0 pp", "trend_text": "Declined."},
            },
        }

        with (
            patch("recommendation_engine._call_groq", return_value=json.dumps(generated)),
            patch("suggestion_ranker.MODEL_PATH", Path("missing_ranker.joblib")),
        ):
            response = generate_recommendations(
                {
                    "gap_analysis": {"all_dimensions": dimensions},
                    "dimension_rows": dimensions,
                    "score_comparison": comparison,
                },
                "Sample School",
                "2026-2027",
            )

        self.assertLess(
            response["recommendations"].index("**D2 Learning Environment**"),
            response["recommendations"].index("**D1 Curriculum**"),
        )
        self.assertEqual(response["recommendations"].count("**D"), 2)
        self.assertEqual(response["recommendations"].count("\n- Action"), 6)

    def test_demo_bridge_flag_is_only_passed_when_requested(self):
        completed = SimpleNamespace(stdout='{"rows":[]}')
        with patch("suggestion_ranker.subprocess.run", return_value=completed) as run:
            _records_from_bridge()
            default_command = run.call_args.args[0]
            self.assertNotIn("--include-demo", default_command)

            _records_from_bridge(include_demo=True)
            demo_command = run.call_args.args[0]
            self.assertIn("--include-demo", demo_command)

    def test_demo_training_uses_separate_model_path(self):
        self.assertEqual(DEMO_MODEL_PATH.name, "suggestion_ranker_demo.joblib")

    def test_validation_holds_out_whole_cycles_for_a_single_school(self):
        labels = [0, 1, 0, 1, 0, 1, 0, 1]
        cycle_ids = [10, 10, 11, 11, 12, 12, 13, 13]

        splits = _cycle_group_splits(
            np.asarray(labels),
            np.asarray(cycle_ids),
        )

        self.assertEqual(len(splits), 4)
        for train_idx, test_idx in splits:
            self.assertTrue(set(labels[i] for i in train_idx) == {0, 1})
            self.assertTrue(set(labels[i] for i in test_idx) == {0, 1})
            self.assertTrue(
                set(cycle_ids[i] for i in train_idx).isdisjoint(
                    cycle_ids[i] for i in test_idx
                )
            )

    def test_validation_requires_multiple_assessment_cycles(self):
        with self.assertRaisesRegex(ValueError, "At least 2 assessment cycles"):
            _cycle_group_splits(np.asarray([0, 1]), np.asarray([10, 10]))

    def test_features_include_dimension_context_and_actionability(self):
        suggestion = {
            "dimension_id": 4,
            "title": "Improve reading resources",
            "text": (
                "- Assign a teacher to prepare a reading list by June 30.\n"
                "- Allocate 10 books per class."
            ),
        }
        dimension = {
            "dimension_no": 2,
            "dimension_name": "Curriculum",
            "score": 62.5,
        }

        features = suggestion_features(suggestion, dimension)

        self.assertEqual(features["dimension_no"], "2")
        self.assertEqual(features["dimension_name"], "Curriculum")
        self.assertEqual(features["dimension_score"], 62.5)
        self.assertEqual(features["action_count"], 2)
        self.assertEqual(features["bullet_count"], 2)
        self.assertEqual(features["numeric_target_count"], 2)
        self.assertGreater(features["responsibility_term_count"], 0)

    def test_missing_model_preserves_original_suggestion_order(self):
        suggestions = [
            {"dimension_id": 1, "title": "First", "text": "First body"},
            {"dimension_id": 2, "title": "Second", "text": "Second body"},
        ]

        with patch("suggestion_ranker.MODEL_PATH", Path("missing_ranker.joblib")):
            ranked, metadata = rank_suggestions(suggestions, [])

        self.assertEqual(ranked, suggestions)
        self.assertFalse(metadata["active"])
        self.assertEqual(metadata["model_source"], "rule_fallback")

    def test_catboost_accepts_training_and_serving_feature_frames(self):
        suggestions = [
            {
                "dimension_no": str(index % 3 + 1),
                "dimension_name": f"Dimension {index % 3 + 1}",
                "dimension_score": float(40 + index),
                "title": f"Plan {index}",
                "text": f"- Assign a team to complete 10 tasks by June {index + 1}.",
            }
            for index in range(12)
        ]
        frame = _frame([
            suggestion_features(item, item)
            for item in suggestions
        ])
        model = _new_model()
        model.set_params(iterations=3)

        model.fit(
            frame,
            [0, 1] * 6,
            cat_features=CATEGORICAL_FEATURES,
        )

        self.assertEqual(model.predict_proba(frame).shape, (12, 2))

    def test_generation_reports_inactive_ranker_without_changing_suggestions(self):
        dimensions = [{
            "dimension_id": 1,
            "dimension_no": 1,
            "dimension_name": "Curriculum",
            "score": 62.5,
        }]
        generated = {
            "suggestions": [{
                "dimension_id": 1,
                "title": "Strengthen reading support",
                "text": "- Assign a teacher.\n- Set a monthly review.\n- Track learner progress.",
                "last_cycle_note": None,
            }]
        }

        with (
            patch("recommendation_engine._call_groq", return_value=json.dumps(generated)),
            patch("suggestion_ranker.MODEL_PATH", Path("missing_ranker.joblib")),
        ):
            response = generate_recommendations(
                {
                    "gap_analysis": {"all_dimensions": dimensions},
                    "dimension_rows": dimensions,
                },
                "Sample School",
                "2026-2027",
            )

        self.assertEqual(response["suggestions"][0]["title"], "D1 Curriculum")
        self.assertFalse(response["suggestion_ranking"]["active"])
        self.assertEqual(response["suggestion_ranking"]["model_source"], "rule_fallback")

    def test_recommendation_confidence_uses_assessment_data_with_rule_fallback(self):
        dimensions = [{
            "dimension_id": 1,
            "dimension_no": 1,
            "dimension_name": "Curriculum",
            "score": 62.5,
        }]
        generated = {
            "suggestions": [{
                "dimension_id": 1,
                "title": "Strengthen curriculum review",
                "text": (
                    "The review identified a gap in indicator 1.2.\n"
                    "- Assign a coordinator to complete a review by June 30.\n"
                    "- Track progress at each monthly meeting.\n"
                    "- Report the results to the school council."
                ),
                "last_cycle_note": None,
            }]
        }
        analysis = {
            "gap_analysis": {"all_dimensions": dimensions},
            "dimension_rows": dimensions,
            "by_rating": {"1": [{"code": "1.2", "rating": 1.0}]},
            "indicator_ratings": {"1.2": 1.0},
            "indicator_response_counts": {"1.2": 5},
            "dimension_evidence": {"1": {
                "rated_indicator_count": 10,
                "response_count": 50,
            }},
            "respondent_consistency": {
                "1.2": {"sh_avg": 1.0, "teacher_avg": 1.0},
            },
            "indicator_history": {"1.2": [1.0, 1.0]},
            "data_completeness": 100,
            "dimension_coverage": {"1": {
                "total_indicators": 10,
                "answered_indicators": 10,
                "dimension_score_available": True,
                "sufficient": True,
            }},
        }

        with (
            patch("recommendation_engine._call_groq", return_value=json.dumps(generated)),
            patch("suggestion_ranker.MODEL_PATH", Path("missing_ranker.joblib")),
        ):
            response = generate_recommendations(
                analysis,
                "Sample School",
                "2026-2027",
            )

        self.assertEqual(response["model_source"], "rule_fallback")
        self.assertEqual(response["blocks"][0]["confidence_pct"], 100.0)
        self.assertEqual(response["blocks"][0]["confidence_level"], "High Confidence")

    def test_high_scoring_cited_indicator_is_not_insufficient(self):
        result = compute_recommendation_confidence(
            "Maintain progress on indicator 1.2.",
            {
                "indicator_ratings": {"1.2": 3.8},
                "indicator_response_counts": {"1.2": 5},
                "dimension_evidence": {"1": {
                    "rated_indicator_count": 10,
                    "response_count": 50,
                }},
                "dimension_coverage": {"1": {
                    "total_indicators": 10,
                    "answered_indicators": 10,
                    "sufficient": True,
                }},
            },
            dimension_id=1,
        )

        self.assertFalse(result["insufficient_data"])
        self.assertEqual(result["confidence_level"], "High Confidence")
        self.assertEqual(result["confidence_pct"], 100.0)

        weak_rating_result = compute_recommendation_confidence(
            "Maintain progress on indicator 1.2.",
            {
                "indicator_ratings": {"1.2": 1.0},
                "indicator_response_counts": {"1.2": 5},
                "dimension_evidence": {"1": {
                    "rated_indicator_count": 10,
                    "response_count": 50,
                }},
                "dimension_coverage": {"1": {
                    "total_indicators": 10,
                    "answered_indicators": 10,
                    "sufficient": True,
                }},
            },
            dimension_id=1,
        )
        self.assertEqual(
            result["confidence_pct"],
            weak_rating_result["confidence_pct"],
        )

        fewer_responses_result = compute_recommendation_confidence(
            "Maintain progress on indicator 1.2.",
            {
                "indicator_ratings": {"1.2": 3.8},
                "indicator_response_counts": {"1.2": 1},
                "dimension_evidence": {"1": {
                    "rated_indicator_count": 10,
                    "response_count": 46,
                }},
                "dimension_coverage": {"1": {
                    "total_indicators": 10,
                    "answered_indicators": 10,
                    "sufficient": True,
                }},
            },
            dimension_id=1,
        )
        self.assertLess(
            fewer_responses_result["confidence_pct"],
            result["confidence_pct"],
        )

        partially_supported_result = compute_recommendation_confidence(
            "Maintain progress on indicators 1.2 and 1.3.",
            {
                "indicator_response_counts": {"1.2": 5},
                "dimension_evidence": {"1": {
                    "rated_indicator_count": 5,
                    "response_count": 25,
                }},
                "dimension_coverage": {"1": {
                    "total_indicators": 10,
                    "answered_indicators": 10,
                    "sufficient": True,
                }},
            },
            dimension_id=1,
        )
        self.assertLess(
            partially_supported_result["confidence_pct"],
            result["confidence_pct"],
        )

    def test_uncited_dimension_actions_still_use_dimension_response_evidence(self):
        result = compute_recommendation_confidence(
            "D1 Curriculum",
            {
                "dimension_coverage": {"1": {
                    "total_indicators": 10,
                    "answered_indicators": 8,
                    "sufficient": True,
                }},
                "dimension_evidence": {"1": {
                    "rated_indicator_count": 8,
                    "response_count": 32,
                }},
            },
            dimension_id=1,
        )

        self.assertFalse(result["insufficient_data"])
        self.assertNotEqual(result["confidence_level"], "General Recommendation")
        self.assertIn("confidence_pct", result)

    def test_uncited_recommendation_with_sufficient_coverage_is_general(self):
        result = compute_recommendation_confidence(
            "Continue strengthening school-wide learning practices.",
            {
                "indicator_ratings": {},
                "indicator_response_counts": {},
                "dimension_coverage": {"1": {"sufficient": True}},
            },
            dimension_id=1,
        )

        self.assertFalse(result["insufficient_data"])
        self.assertEqual(result["confidence_level"], "General Recommendation")
        self.assertNotIn("confidence_pct", result)

    def test_insufficient_dimension_coverage_returns_insufficient_data(self):
        result = compute_recommendation_confidence(
            "Improve indicator 1.2.",
            {
                "indicator_ratings": {"1.2": 1.5},
                "indicator_response_counts": {"1.2": 1},
                "dimension_coverage": {"1": {
                    "total_indicators": 10,
                    "answered_indicators": 4,
                    "dimension_score_available": True,
                    "sufficient": False,
                }},
            },
            dimension_id=1,
        )

        self.assertTrue(result["insufficient_data"])
        self.assertIn("Response coverage is too low", result["reason"])

    def test_groq_backend_is_used_for_generation(self):
        dimensions = [{
            "dimension_id": 1,
            "dimension_no": 1,
            "dimension_name": "Curriculum",
            "score": 62.5,
        }]
        generated = {
            "suggestions": [{
                "dimension_id": 1,
                "title": "Strengthen reading support",
                "text": (
                    "- Assign a teacher to coordinate support.\n"
                    "- Review learner progress monthly.\n"
                    "- Track results by the end of term."
                ),
                "last_cycle_note": None,
            }]
        }

        with (
            patch("recommendation_engine._call_groq", return_value=json.dumps(generated)) as groq,
            patch("recommendation_engine._call_ollama", side_effect=AssertionError("Ollama must not run")),
            patch("suggestion_ranker.MODEL_PATH", Path("missing_ranker.joblib")),
            patch.dict("os.environ", {"GROQ_MODEL": "test-groq-model"}),
        ):
            response = generate_recommendations(
                {
                    "gap_analysis": {"all_dimensions": dimensions},
                    "dimension_rows": dimensions,
                },
                "Sample School",
                "2026-2027",
                backend="groq",
            )

        groq.assert_called_once()
        self.assertEqual(response["backend_used"], "test-groq-model")
        self.assertEqual(response["blocks"][0]["model_version"], "test-groq-model")
        self.assertIn("external groq service", response["privacy_notice"])

    def test_groq_requests_json_mode_and_rejects_empty_content(self):
        completion = SimpleNamespace(
            choices=[SimpleNamespace(
                message=SimpleNamespace(content='{"suggestions":[]}'),
                finish_reason="stop",
            )],
            model="test-model",
        )
        client = SimpleNamespace(
            chat=SimpleNamespace(
                completions=SimpleNamespace(
                    create=Mock(return_value=completion),
                )
            )
        )
        openai_module = SimpleNamespace(OpenAI=Mock(return_value=client))

        with (
            patch.dict("os.environ", {
                "GROQ_API_KEY": "test-key",
                "GROQ_MODEL": "test-model",
                "GROQ_MAX_TOKENS": "2048",
            }),
            patch.dict("sys.modules", {"openai": openai_module}),
        ):
            result = _call_groq("Return a JSON object.", json_mode=True)

        self.assertEqual(result, '{"suggestions":[]}')
        openai_module.OpenAI.assert_called_once()
        client.chat.completions.create.assert_called_once()
        self.assertEqual(
            client.chat.completions.create.call_args.kwargs["response_format"],
            {"type": "json_object"},
        )
        self.assertEqual(
            client.chat.completions.create.call_args.kwargs["max_tokens"],
            2048,
        )

        completion.choices[0].message.content = None
        with (
            patch.dict("os.environ", {
                "GROQ_API_KEY": "test-key",
                "GROQ_MODEL": "test-model",
                "GROQ_MAX_TOKENS": "2048",
            }),
            patch.dict("sys.modules", {"openai": openai_module}),
        ):
            with self.assertRaisesRegex(RuntimeError, "Groq returned an empty message"):
                _call_groq("Return a JSON object.", json_mode=True)

    def test_generate_ip_field_uses_plain_text_and_extracts_nested_text(self):
        with patch("recommendation_engine._call_backend", return_value='{"text": "Increase reading support for Grade 4 learners."}') as call_backend:
            result = generate_ip_field(
                field_type="objective",
                indicator_code="1.6",
                indicator_text="Teachers integrate topic promoting peace and DepEd values.",
                dimension_name="Curriculum and Teaching",
                snippet="**Improve classroom practice**\n- Use more contextualized learning materials.",
                backend="groq",
            )

        self.assertEqual(result["text"], "Increase reading support for Grade 4 learners.")
        self.assertEqual(result["field_type"], "objective")
        self.assertIsNone(result["error"])
        call_backend.assert_called_once()
        self.assertEqual(call_backend.call_args.args[1], "groq")
        self.assertFalse(call_backend.call_args.kwargs["json_mode"])

    def test_groq_token_budget_is_validated(self):
        with patch.dict("os.environ", {"GROQ_MAX_TOKENS": "70000"}):
            with self.assertRaisesRegex(RuntimeError, "between 1 and 65536"):
                from recommendation_engine import _groq_max_tokens

                _groq_max_tokens()

    def test_parser_discards_history_note_without_historical_context(self):
        dimensions = [{
            "dimension_id": 1,
            "dimension_no": 1,
            "dimension_name": "Curriculum",
        }]
        generated = json.dumps({
            "suggestions": [{
                "dimension_id": 1,
                "title": "Improve curriculum review",
                "text": "Review the curriculum with teaching staff.",
                "last_cycle_note": "No previous suggestion was available.",
            }]
        })

        parsed = _parse_dimension_suggestions(generated, dimensions)

        self.assertIsNone(parsed[0]["last_cycle_note"])

    def test_parser_removes_repeated_title_from_suggestion_body(self):
        dimensions = [{
            "dimension_id": 1,
            "dimension_no": 1,
            "dimension_name": "Curriculum",
        }]
        generated = json.dumps({
            "suggestions": [{
                "dimension_id": 1,
                "title": "Improve curriculum review",
                "text": (
                    "**Improve curriculum review**\n"
                    "Review the curriculum with teaching staff.\n"
                    "- Curriculum Coordinator completes a review each term."
                ),
            }]
        })

        parsed = _parse_dimension_suggestions(generated, dimensions)

        self.assertEqual(
            parsed[0]["text"],
            "Review the curriculum with teaching staff.\n"
            "- Curriculum Coordinator completes a review each term.",
        )
        self.assertEqual(parsed[0]["action_count"], 1)

    def test_parser_builds_history_note_from_trusted_cycle_data(self):
        dimensions = [{
            "dimension_id": 1,
            "dimension_no": 1,
            "dimension_name": "Curriculum",
        }]
        previous = [{
            "dimension_no": 1,
            "has_previous_suggestion": True,
            "suggestion_title": "Improve reading supports",
            "score_at_time": 70.0,
            "current_score": 74.1,
            "score_change": 4.1,
            "plan_created": False,
        }]
        generated = json.dumps({
            "suggestions": [{
                "dimension_id": 1,
                "title": "Improve curriculum review",
                "text": "Review the curriculum with teaching staff.",
                "last_cycle_note": "The model's invalid note is ignored.",
            }]
        })

        parsed = _parse_dimension_suggestions(generated, dimensions, previous)

        self.assertEqual(
            parsed[0]["last_cycle_note"],
            'Last cycle: "Improve reading supports"; Result: score improved by 4.1 '
            "from 70.0% to 74.1%, and no improvement plan was created.",
        )


if __name__ == "__main__":
    unittest.main()
