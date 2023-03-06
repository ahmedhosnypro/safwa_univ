// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @module     theme_remui/profile
 * @copyright (c) 2023 WisdmLabs (https://wisdmlabs.com/)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

"use strict";
define([
    'jquery',
    'core/ajax',
    'core/notification'
], function($, Ajax, Notification) {
    $('#editprofile .form-horizontal #btn-save-changes').click(function() {
        var SELECTORS = {
            ERROR: 'div#error-message',
            DANGER: 'alert-danger',
            SUCCESS: 'alert-success'
        };
        $(SELECTORS.ERROR).show();
        $(SELECTORS.ERROR).removeClass(SELECTORS.DANGER).addClass(SELECTORS.SUCCESS);
        $(SELECTORS.ERROR).find('p').html("Saving...");

        var fname = $('#first_name').val();
        var lname = $('#surname').val();
        var description = $.trim($('#description').val());
        var city = $.trim($('#city').val());
        var country = $('#editprofile .form-horizontal #country option:selected').val();

        if (fname === '') {
            $(SELECTORS.ERROR).show();
            $(SELECTORS.ERROR).removeClass(SELECTORS.SUCCESS).addClass(SELECTORS.DANGER);
            $(SELECTORS.ERROR).find('p').html(M.util.get_string('enterfirstname', 'theme_remui'));
            $('#first_name').focus();
            return false;
        }
        if (lname === '') {
            $(SELECTORS.ERROR).show();
            $(SELECTORS.ERROR).removeClass(SELECTORS.SUCCESS).addClass(SELECTORS.DANGER);
            $(SELECTORS.ERROR).find('p').html(M.util.get_string('enterlastname', 'theme_remui'));
            $('#surname').focus();
            return false;
        }

        var promise = Ajax.call([{
            methodname: 'theme_remui_save_user_profile_settings',
            args: {
                fname,
                lname,
                description,
                city,
                country
            }
        }])[0];
        promise.done(function() {
                $(SELECTORS.ERROR).show();
                $(SELECTORS.ERROR).removeClass(SELECTORS.DANGER).addClass(SELECTORS.SUCCESS);
                $(SELECTORS.ERROR).find('p').css('margin', '0').html(M.util.get_string('detailssavedsuccessfully', 'theme_remui'));
                $('.profile-user').text(fname + " " + lname);
                $('.usermenu a span.usertext').text(fname + " " + lname);
                $('#user-description').text(description);
            })
            .fail(function(ex) {
                $(SELECTORS.ERROR).removeClass(SELECTORS.SUCCESS).addClass(SELECTORS.DANGER);
                $(SELECTORS.ERROR).find('p')
                    .css('margin', '0')
                    .html(ex.errorcode + ' : ' + ex.error + ', ' + M.util.get_string('actioncouldnotbeperformed', 'theme_remui'));
                Notification.exception(ex);
            });

        return false;
    });

});
