/* eslint-disable no-console */
/* eslint-disable no-unused-vars */
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
 * @module     theme_remui/enrolpage
 * @copyright (c) 2023 WisdmLabs (https://wisdmlabs.com/)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax', 'core/templates', 'core/notification'], function($, Ajax, Templates, Notification) {
    const linkcoursecontent = '#linkcoursecontent';
    // Const activeloading = ".loading.active";
    const activeloadingpane = ".tab-pane.active.loading";
    const loadingnavlink = ".nav-link.loading";
    const tabpanearea = ".tab-pane-area";
    const displayException = (ex) => {
        console.error(ex);
    };

    const activateContentLoading = (_thispane) => {
        var serviceName = $(_thispane).attr("data-service");
        var templateName = $(_thispane).attr("data-template");
        if (serviceName) {
            var autoservice = Ajax.call([{
                methodname: serviceName,
                args: {courseid: M.cfg.courseId}
            }]);
            autoservice[0].done(function(response) {
                Templates.renderForPromise(templateName, response)
                    .then(({html, js}) => {
                        $(_thispane).find(tabpanearea).empty();
                        Templates.appendNodeContents(_thispane, html, js);
                        $('.nav-link[href="#' + $(_thispane).attr('id') + '"]').removeClass('loading');
                        $(_thispane).removeClass('loading');
                    }).catch(ex => displayException(ex));
            }).fail(Notification.exception);
        }
    };

    return {
        init: function() {
            $(document).ready(function() {
                activateContentLoading(activeloadingpane);
                $(document).on('click', loadingnavlink, function() {
                    var loadContentForPane = $(this).attr("href");
                    activateContentLoading(loadContentForPane);
                });
            });
        }
    };
});
