import unittest

from score_analyzer import full_analysis


class FullAnalysisWeakIndicatorTests(unittest.TestCase):
    def test_php_rating_field_counts_weak_indicators(self):
        result = full_analysis(
            {
                "dim_scores": {"1": 60.0},
                "indicators": [
                    {
                        "dimension_no": 1,
                        "indicator_code": "1.1",
                        "indicator_text": "Indicator 1",
                        "rating": 2.4,
                    },
                    {
                        "dimension_no": 1,
                        "indicator_code": "1.2",
                        "indicator_text": "Indicator 2",
                        "rating": 2.5,
                    },
                    {
                        "dimension_no": 1,
                        "indicator_code": "1.3",
                        "indicator_text": "Indicator 3",
                        "rating": 3.0,
                    },
                ],
                "history": [],
            }
        )

        dimension = result["gap_analysis"]["all_dimensions"][0]
        self.assertEqual(dimension["weak_count"], 1)
        self.assertEqual(dimension["weak_ratio"], 0.333)
        self.assertEqual(len(result["weak_indicators"]["by_dimension"][1]), 1)


if __name__ == "__main__":
    unittest.main()
