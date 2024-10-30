/*
    Name: controllable-fields.js
    Author: AuRise Creative | https://aurisecreative.com
    Last Modified: August 31, 2024 @ 22:32

    To use this feature...

    1. Add a "au-cf7-controller" class to the radio, checkbox, select, or number/range HTML elements that will be controlling others
        - Checkbox: displays the controlled fields when checked and hides when unchecked.
        - Radio:    displays the controlled fields when checked and hides the rest.
        - Select:   displays the controlled fields when they match the value that is selected and hides the rest.
        - Number:   displays the controlled fields based on number ranges set in the controlled element's values.
    2. Controlled fields should have a data-controller attribute on its wrapping element set to the ID attribute of its controller
        within the same form. It can have multiple controllers, with ID using pipes (|) to separate them.
    3. Controlled fields should have a "au-cf7-hidden-by-controller" class added to its wrapping element to hide it by default. This
        feature simply toggles that class on/off, so you'll need CSS to actually hide it based on that class.
    4. Controlled fields controlled by a radio button, select field, or number field, should have a `data-values` attribute set to a
        pipe separated list of the values used to display it on the wrapping element. Values can include operations such as >, >=,
        <, <=, !=, or *. The operation = is assumed when no comparison is found.
    5. If it has multiple controllers, then there should be a `data-{controller ID}-values` attribute that sets the values for that
        specific controller.
    5. If the controlled field should be required when displayed, instead of adding the required attribute to the input/select field,
        add the "required-when-visible" to the class attribute.
    6. It is possible to nest controllers.
    7. Controlled fields with multiple controllers are only hidden if all controllers evaluate to no matches. Otherwise, it will
        remain visible. Multiple controllers are treated as an OR comparison, meaning at least one controller must be active for
        the controlled field to display. Nest them to treat them as an AND operation.
*/
window['$'] = window['$'] || jQuery.noConflict();
const aurise_controllable_fields_cf7 = {
    version: '2024.04.27.10.41',
    init: function() {
        // Controllable fields
        let click_controllers = '.wpcf7 form input[type=checkbox].au-cf7-controller,.wpcf7 form input[type=radio].au-cf7-controller',
            select_controllers = '.wpcf7 form select.au-cf7-controller',
            text_controllers = '.wpcf7 form input[type=text].au-cf7-controller,.wpcf7 form input[type=email].au-cf7-controller,.wpcf7 form input[type=hidden].au-cf7-controller,.wpcf7 form input[type=password].au-cf7-controller,.wpcf7 form input[type=search].au-cf7-controller,.wpcf7 form input[type=tel].au-cf7-controller,.wpcf7 form input[type=url].au-cf7-controller',
            interactive_controllers = '.wpcf7 form input[type=number].au-cf7-controller,.wpcf7 form input[type=range].au-cf7-controller',
            controllers = $(click_controllers + ',' + select_controllers + ',' + interactive_controllers + ',' + text_controllers); // All controllers
        if (controllers.length) {

            //Add controllable field listeners
            $(click_controllers).on('click', aurise_controllable_fields_cf7.toggleHandler);
            $(select_controllers).on('change', aurise_controllable_fields_cf7.toggleHandler);
            $(text_controllers).on('change keyup blur', aurise_controllable_fields_cf7.toggleHandler);
            $(interactive_controllers).on('change keyup blur mousemove mousedown mouseup', aurise_controllable_fields_cf7.toggleHandler);

            // Initialize default states
            let c1 = 1;
            controllers.each(function() {
                let $controller = $(this),
                    controller_name = $controller.attr('name');

                // If this controller does not have an ID attribute, add a temporary one
                let temp_id = false;
                if ($controller.attr('id') === undefined) {
                    temp_id = 'aurise-cf7-cf-temp-controller-id-' + controller_name + '-' + c1;
                    $controller.attr('id', temp_id);
                }

                let id = $controller.attr('id'),
                    cid = aurise_controllable_fields_cf7.getControllerId(this),
                    $controlled = $('[data-controller*="' + (temp_id ? controller_name : cid) + '"]');
                if ($controlled.length) {
                    if (temp_id) {
                        $controlled.attr('data-controller', $controlled.attr('data-controller').replace(controller_name, id));
                    }
                    let controlled_value = $controller.is('input[type=checkbox]') ? aurise_controllable_fields_cf7.getCheckbox($controller) : $controller.val(),
                        ariaControls = [];
                    aurise_controllable_fields_cf7.toggleControlledFields(cid, id, controlled_value);
                    // Update aria attributes
                    $controlled.each(function() {
                        let c2 = 1,
                            $thisControlled = $(this),
                            thisControlledId = $thisControlled.attr('id'),
                            thisLabel = $thisControlled.attr('aria-labelledby');
                        if (!thisControlledId) {
                            // Generate a random ID
                            thisControlledId = 'aurise-cf7-cf-temp-controlled-id-' + cid + '-' + c1 + '-' + c2;
                            $thisControlled.attr('id', thisControlledId);
                        }
                        ariaControls.push(thisControlledId);
                        if (!thisLabel) {
                            thisLabel = id;
                        } else {
                            thisLabel += ' ' + id;
                        }
                        $thisControlled.attr({ 'role': 'group', 'aria-labelledby': thisLabel });
                        c2++;
                    });
                    ariaControls = ariaControls.join(' ');
                    $controller.attr({ 'aria-controls': ariaControls, 'aria-owns': ariaControls });
                } else {
                    console.warn('No controlled elements found for controller: ' + cid);
                    // If I am a checkbox or radio button, look for my name object instead
                }
                $controller.trigger('controller_init');
                c1++;
            });
        }
    },
    getControllerId: function(input) {
        let $controller = $(input),
            id = $controller.attr('id'),
            id2 = $controller.attr('data-id');
        // If there is a data-id attribute, return that
        if (id2) {
            return id2;
        }
        // If I don't have an id attribute, return my name attribute
        if (!id || $controller.is('[type=radio]') || $controller.is('[type=checkbox]')) {
            return $controller.attr('name').replace('[]', '');
        }
        return id; // Return my id attribute
    },
    getControllerById: function(cid) {
        // The cid could be the id attribute or data-id attribute
        let $controller = $('[data-id="' + cid + '"].au-cf7-controller');
        if ($controller.length) {
            return $controller;
        }
        $controller = $('#' + cid + '.au-cf7-controller');
        if ($controller.length) {
            return $controller;
        }
        return false;
    },
    getCheckbox: function(input) {
        //Returns a true/false boolean value based on whether the checkbox is checked
        let $input = $(input);
        return ($input.is(':checked') || $input.prop('checked'));
    },
    toggleCheckbox: function(input, passedValue) {
        //Changes a checkbox input to be checked or unchecked based on boolean parameter (or toggles if not included)
        //Only changes it visually - it does not change any data in any objects
        let $input = $(input),
            value = passedValue;
        if (typeof(value) != 'boolean' || value === undefined) {
            value = !aurise_controllable_fields_cf7.getCheckbox($input);
        }
        if (value) {
            $input.attr('checked', 'checked');
            $input.prop('checked', true);
        } else {
            $input.removeAttr('checked');
            $input.prop('checked', false);
        }
        input.trigger('toggled_checkbox');
    },
    number: {
        changing: false,
        check: false
    },
    toggleHandler: function(e) {
        // Handle the number spinbox and ranged slider
        if (typeof(e) == 'object') {
            if (e.type == 'mousedown') {
                // Mouse is being clicked down, turn it on
                aurise_controllable_fields_cf7.number.changing = true;
                aurise_controllable_fields_cf7.number.check = setInterval(function() {
                    if (aurise_controllable_fields_cf7.number.changing) {
                        aurise_controllable_fields_cf7.toggleHandler($(e.target).attr('id'));
                    }
                }, 500);
            } else if (e.type == 'mouseup' && aurise_controllable_fields_cf7.number.changing) {
                // Mouse was released after changing the number, turn it all off
                aurise_controllable_fields_cf7.number.changing = false;
                clearInterval(aurise_controllable_fields_cf7.number.check);
                return;
            } else if ((e.type == 'mousemove' && !aurise_controllable_fields_cf7.number.changing) || e.type == 'mouseup') {
                // If mouse is moving but numbers aren't changing, or mouse was released but we don't care, bail
                return;
            }
        }
        let $controller = typeof(e) == 'string' ? $('#' + e) : $(e.target),
            id = $controller.attr('id'),
            cid = aurise_controllable_fields_cf7.getControllerId($controller);
        aurise_controllable_fields_cf7.toggleControlledFields(cid, id, null);
    },
    toggleControlledFields: function(cid, id, forcedToggle) {
        let $controller = $('#' + id);
        if ($controller.length < 1) { console.warn('Controller #' + id + '" does not exist!'); return; }
        let is_checkbox = $controller.is('[type=checkbox]'),
            is_radio = $controller.is('[type=radio]'),
            is_select = $controller.is('select'),
            is_toggle = is_checkbox || is_radio,
            toggle_value = is_toggle ? aurise_controllable_fields_cf7.getCheckbox($controller) : null,
            is_expanded = false,
            //is_number = $controller.is('[type=number]'),
            $controlledObjs = $('[data-controller*="' + cid + '"]'),
            $empty_controlled = false;
        if ($controlledObjs.length < 1) { console.warn('No controlled elements found for controller: ' + cid); return; }
        let controlled_value = forcedToggle === null || forcedToggle === undefined ? $controller.val() : forcedToggle,
            count_displayed = 0;

        // Loop through each controlled element; the value must match that of the input to display it
        $controlledObjs.each(function(co, controlled) {
            let $controlled = $(controlled),
                // If I have multiple controllers, get the values for this specific controller
                multiControllers = $controlled.attr('data-controller').indexOf('|') >= 0,
                acceptedValues = multiControllers ? $controlled.attr('data-' + cid + '-values') : $controlled.attr('data-values'),
                matches = 0,
                displayMe = false;

            //console.log(acceptedValues, toggle_value);
            if (acceptedValues === '' || acceptedValues === undefined) {
                $empty_controlled = $controlled;

            }
            matches += aurise_controllable_fields_cf7.compareLoop(acceptedValues, controlled_value, is_toggle, toggle_value);
            if (matches > 0) {
                // This controlled element's value matches what was selected, display it
                count_displayed++;
                $controlled.removeClass('au-cf7-hidden-by-controller');
                displayMe = true;
            } else {
                // Before hiding, we need to check if I have multiple controllers and if I do, if any of them say I should still be shown
                if (multiControllers) {
                    matches = 0;
                    let controllers = $controlled.attr('data-controller').split('|');
                    $.each(controllers, function(c, other_cid) {
                        // Skip checking the controller this is for, just check the other ones
                        if (other_cid !== cid) {
                            let $other_controller = aurise_controllable_fields_cf7.getControllerById(other_cid),
                                other_controlled_value = $other_controller.val(),
                                is_other_toggle = $other_controller.is('[type=checkbox]') || $other_controller.is('[type=radio]'),
                                otherAcceptedValues = $other_controller.attr('data-' + other_cid + '-values');
                            matches += aurise_controllable_fields_cf7.compareLoop(
                                otherAcceptedValues,
                                other_controlled_value,
                                is_other_toggle,
                                is_other_toggle ? aurise_controllable_fields_cf7.getCheckbox($other_controller) : null
                            );
                        }
                    });
                    if (matches > 0) {
                        // This controlled element's value matches what was selected, display it
                        count_displayed++;
                        $controlled.removeClass('au-cf7-hidden-by-controller');
                        displayMe = true;
                    } else {
                        // This controlled element's value DOES NOT match what was selected, hide it
                        $controlled.addClass('au-cf7-hidden-by-controller');
                    }
                } else {
                    // Before hiding, we need to check if the checkbox value is still checked because we could just be checking/unchecking a different box of the same group
                    if (is_toggle) {
                        if (is_checkbox) {
                            // Skip checking the controller this is for, just check the other checked checkboxes in the set
                            let controllers = $controller.closest('form').find('input[type=checkbox][name="' + $controller.attr('name') + '"]:checked:not(#' + $controller.attr('id') + ')');
                            if (controllers.length) {
                                $.each(controllers, function(c, other_controller) {
                                    let $other_controller = $(other_controller);
                                    is_other_toggle = $other_controller.is('[type=checkbox]') || $other_controller.is('[type=radio]');
                                    matches += aurise_controllable_fields_cf7.compareLoop(
                                        acceptedValues,
                                        $other_controller.val(),
                                        is_other_toggle, is_other_toggle ? aurise_controllable_fields_cf7.getCheckbox(other_controller) : null
                                    );
                                });
                            }
                        }
                    }

                    if (matches > 0) {
                        // This controlled element's value matches what was selected, display it
                        count_displayed++;
                        $controlled.removeClass('au-cf7-hidden-by-controller');
                        displayMe = true;
                    } else {
                        // This controlled element's value DOES NOT match what was selected, hide it
                        $controlled.addClass('au-cf7-hidden-by-controller');
                    }
                }
            }

            // Handle nested required fields: TO-DO: filter out nested-requireds?
            let $required_fields = displayMe ? $controlled.find('.required-when-visible') : $controlled.find('[required]')
            if ($required_fields.length > 0) {
                if (displayMe) {
                    // Add the required attributes because they are now visible
                    $required_fields.each(function(rf, required_field) {
                        $(required_field).attr({ 'required': 'required', 'aria-required': 'true' });
                    });
                } else {
                    // Remove the required attributes because they are being hidden
                    $required_fields.each(function(rf, required_field) {
                        let $thisRequiredField = $(required_field);
                        if (!$thisRequiredField.hasClass('required-when-visible')) {
                            $thisRequiredField.addClass('required-when-visible');
                        }
                        $thisRequiredField.attr('aria-required', 'false').removeAttr('required');
                    });
                }
            }

            // Hide nested controllers
            if (!displayMe) {
                let $nested_controllers = $controlled.find('.au-cf7-controller');
                if ($nested_controllers.length) {
                    $nested_controllers.each(function(nc, nested_controller) {
                        let $nested_controller = $(nested_controller);
                        aurise_controllable_fields_cf7.toggleCheckbox($nested_controller, false);
                        aurise_controllable_fields_cf7.toggleControlledFields(aurise_controllable_fields_cf7.getControllerId($nested_controller), $nested_controller.attr('id'), false);
                    });
                }
            } else if (!is_expanded) {
                is_expanded = true;
            }
        });

        // Update ARIA flag on controller
        if (is_expanded) {
            $controller.attr('aria-expanded', 'true');
            $controller.trigger('displayed_controlled');
        } else if (is_toggle && $empty_controlled !== false && !$('input[name="' + $controller.attr('name') + '"]:checked').length) {
            // If they're all unchecked and there is a "default" to display, display it
            $controller.attr('aria-expanded', 'true');
            $empty_controlled.removeClass('au-cf7-hidden-by-controller');
            $controller.trigger('default_controlled');
        } else {
            $controller.attr('aria-expanded', 'false');
            $controller.trigger('hide_controlled');
        }
        $controller.trigger('toggled_controlled');
    },
    compareLoop: function(acceptedValues, controlled_value, is_toggle, toggle_value) {
        let matches = 0;
        if (acceptedValues.indexOf('|') >= 0) {
            acceptedValues = acceptedValues.split('|');
        } else {
            acceptedValues = [acceptedValues];
        }
        $.each(acceptedValues, function(i, acceptedValue) {
            if (!is_toggle || (is_toggle && toggle_value)) {
                // If neither a checkbox nor select field, continue to check for matches
                // For checkboxes and radios to possibly match at all, it must also be checked
                matches += aurise_controllable_fields_cf7.compareValues(acceptedValue, controlled_value, is_toggle);
            }
        });
        return matches;
    },
    compareValues: function(value, controlled_value, is_toggle) {
        /**
         * value = the controller's value that it should match to be true, set by the user
         * controlled_value = the controlled element's value that is being tested, an option
         */
        if (controlled_value === true) {
            // Treat boolean true as 1
            controlled_value = '1';
        } else if (controlled_value === null || controlled_value === undefined || typeof(controlled_value) != 'string') {
            // Treat null and undefined values as empty strings
            controlled_value = '';
        }
        controlled_value = controlled_value.trim();
        if (value === true) {
            // Treat boolean true as 1
            value = '1';
        } else if (value === null || value === undefined || typeof(value) != 'string') {
            value = '';
        }
        value = value.trim();

        // Global value checks
        if (value == '' || value == 'BLANK' || value == 'EMPTY') {
            // Match only empty/blank
            if (controlled_value == '') {
                return 1;
            }
            return 0;
        } else if (value == 'EMPTY_OR_ZERO' || value == 'FALSEY') {
            // Match falsey values
            if (!controlled_value || controlled_value == 0 || controlled_value == 'false') {
                return 1;
            }
            return 0;
        } else if (value == '*') {
            // Match anything that isn't falsy
            if (!controlled_value || controlled_value == 0 || controlled_value == 'false') {
                return 0;
            }
            return 1;
        }

        // If controlled value is a number, do numerical checks
        if (controlled_value != '' && value != '' && !isNaN(controlled_value)) {
            controlled_value = parseFloat(controlled_value);

            // If the value isn't a number, then there's probably operations involved
            if (isNaN(value) || value.startsWith('--')) {
                // Check for a range, e.g. 10-20, -10 (less than 10) or 50- (greater than 50)
                if (value.indexOf('-') > -1) {
                    if (value.startsWith('--')) {
                        // If the value is -10, then the controlled value is less than or equal to 10
                        value = value.replace('--', '<='); // Replace with the appropriate operation
                    } else if (value.endsWith('--')) {
                        // If the value is 10-, then the controlled value is greater than or equal to 10
                        value = '>=' + value.replace('--', ''); // Replace with the appropriate operation
                    } else {
                        // Do a "between" comparison
                        let values = value.split('-');
                        if (values.length > 1) {

                            let min = parseFloat(values[0]),
                                max = parseFloat(values[1]);
                            if (min <= controlled_value && controlled_value <= max) {
                                return 1;
                            }
                        }
                        return 0;
                    }
                }

                // Single numeric comparisons
                if (value.startsWith('!=')) {
                    // Match everything else except this value
                    if (controlled_value != value.replace('!=', '')) {
                        return 1;
                    }
                    return 0;
                } else if (value.startsWith('<=')) {
                    if (controlled_value <= parseFloat(value.replace('<=', ''))) {
                        return 1;
                    }
                    return 0;
                } else if (value.startsWith('<')) {
                    if (controlled_value < parseFloat(value.replace('<', ''))) {
                        return 1;
                    }
                    return 0;
                } else if (value.startsWith('>=')) {
                    if (controlled_value >= parseFloat(value.replace('>=', ''))) {
                        return 1;
                    }
                    return 0;
                } else if (value.startsWith('>')) {
                    if (controlled_value > parseFloat(value.replace('>', ''))) {
                        return 1;
                    }
                    return 0;
                }
            } else {
                value = parseFloat(value);
            }
        }
        return controlled_value == value ? 1 : 0;
    }
};
$(document).ready(aurise_controllable_fields_cf7.init);