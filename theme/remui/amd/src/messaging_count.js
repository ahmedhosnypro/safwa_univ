/* eslint-disable no-console*/
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
 * @module     theme_remui/messaging_count
 * @copyright (c) 2023 WisdmLabs (https://wisdmlabs.com/)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(
    ['jquery',
        'core/ajax',
        'core/notification',
        'core/pubsub',
        'core_message/message_drawer_router',
        'core_message/message_drawer_routes',
        'core_message/message_drawer_events',
    ],
    function(
        $,
        Ajax,
        Notification,
        PubSub,
        Router,
        Routes,
        MessageDrawerEvents,
    ) {

        const getUserCount = function() {
            Ajax.call([{
                methodname: 'theme_remui_get_msg_contact_list_count',
                args: {
                    userid: 1
                },
                done: function(data) {
                    $('.show-contacts-section span').remove();
                    $('.show-contacts-section').append(data);
                },
                fail: function() {
                    console.log(Notification.exception);
                }
            }]);
        };
        const getLogInUserdetails = function() {
            Ajax.call([{
                methodname: 'theme_remui_get_login_user_detail',
                args: {},
                done: function(data) {
                    $(".send .messager-info .messager-img-container").remove();
                    $(".send .messager-info .username").remove();
                    $(".send .messager-info").append(data);
                },
                fail: function() {
                    console.log(Notification.exception);
                }
            }]);
        };

        const init = function() {
            $(document).ready(function() {
                getUserCount();
                PubSub.subscribe(MessageDrawerEvents.CONVERSATION_READ, function() {
                    getLogInUserdetails();
                });

                // This events are used to handle the contact counts on contacts page.
                PubSub.subscribe(MessageDrawerEvents.CONTACT_ADDED, function() {
                    getUserCount();
                });
                PubSub.subscribe(MessageDrawerEvents.CONTACT_REMOVED, function() {
                    getUserCount();
                });
                PubSub.subscribe(MessageDrawerEvents.CONTACT_REQUEST_ACCEPTED, getUserCount());

            });
        };
        return {
            init: init
        };
    });
