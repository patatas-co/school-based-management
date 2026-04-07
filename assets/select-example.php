/**
 * Integration Example: Using Modern Select in PHP
 * =================================================
 *
 * STEP 1: Include CSS and JS in your PHP file (before </head> or in header)
 * -------------------------------------------------------------------------
 *
 * <link rel="stylesheet" href="<?= $base ?>/assets/select.css">
 * <script src="<?= $base ?>/assets/select.js" defer></script>
 *
 *
 * STEP 2: Add data-select="true" to your <select> elements
 * ----------------------------------------------------------
 *
 * <select class="fc" data-select="true" data-placeholder="Choose a role" id="c_role">
 *   <option value="">Choose a role</option>
 *   <option value="school_head">School Head</option>
 *   <option value="sbm_coordinator">SBM Coordinator</option>
 *   <option value="teacher">Teacher</option>
 * </select>
 *
 *
 * STEP 3: That's it! The JS auto-initializes on page load.
 * ---------------------------------------------------------
 *
 *
 * ================================================
 * CUSTOMIZATION OPTIONS
 * ================================================
 *
 * Size variants:
 *   data-size="sm"  - Small (28px height)
 *   data-size="md"  - Medium (36px height) [default]
 *   data-size="lg"  - Large (40px height)
 *
 * Placeholder text:
 *   data-placeholder="Select your option"
 *
 * Full width:
 *   data-full-width="true"
 *
 * Disabled state:
 *   Just add the disabled attribute to native select
 *   <select class="fc" disabled>...</select>
 *
 *
 * ================================================
 * EXAMPLE: Converting existing selects
 * ================================================
 *
 * BEFORE:
 * <select class="fc" id="c_role">
 *   <option value="school_head">School Head</option>
 *   <option value="teacher">Teacher</option>
 * </select>
 *
 * AFTER:
 * <select class="fc" id="c_role" data-select="true" data-placeholder="Select role">
 *   <option value="">Select role</option>
 *   <option value="school_head">School Head</option>
 *   <option value="teacher">Teacher</option>
 * </select>
 *
 *
 * ================================================
 * OPTGROUP SUPPORT
 * ================================================
 *
 * <select class="fc" data-select="true" data-placeholder="Select item">
 *   <optgroup label="Group 1">
 *     <option value="a">Option A</option>
 *     <option value="b">Option B</option>
 *   </optgroup>
 *   <optgroup label="Group 2">
 *     <option value="c">Option C</option>
 *   </optgroup>
 * </select>
 *
 *
 * ================================================
 * FORM SUBMISSION NOTE
 * ================================================
 *
 * The native <select> element is kept in DOM but hidden.
 * It will submit its value normally as part of any form.
 * No additional hidden input handling needed!
 *
 */
