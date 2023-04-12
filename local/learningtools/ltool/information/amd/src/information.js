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
 * information ltool define js.
 * @module   ltool_information
 * @category  Classes - autoloading
 * @copyright 2021, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 define(['jquery', 'core/modal_factory', 'core/str', 'core/fragment', 'core/modal_events', 'core/ajax', 'core/notification'],
 function($, ModalFactory, Str, Fragment, ModalEvents, Ajax, notification) {

    /**
     * Controls information tool action.
     * @param {object} params
     */
    function learningToolinformationAction(params) {
        showModalinformationtool(params);
    }

    /**
     * Display the modal to course info.
     * @param {object} params
     */
    function showModalinformationtool(params) {
        var informationinfo = document.querySelector(".ltoolinformation-info #ltoolinformation-action");
        if (informationinfo) {
            informationinfo.addEventListener("click", function() {
                // Strinformationusers.
                ModalFactory.create({
                    title: params.coursename,
                    type: ModalFactory.types.CANCEL,
                    body: getCourseInfoModal(params),
                    large: true
                }).then(function(modal) {
                    modal.show();
                    modal.getRoot().on(ModalEvents.hidden, function() {
                        modal.destroy();
                    });
                    return modal;
                }).catch(notification.exception);
            });
            // Hover color.
            var informationhovercolor = informationinfo.getAttribute("data-hovercolor");
            var informationfontcolor = informationinfo.getAttribute("data-fontcolor");
            if (informationhovercolor && informationfontcolor) {
                informationinfo.addEventListener("mouseover", function() {
                    document.querySelector('#ltoolinformation-info p').style.background = informationhovercolor;
                    document.querySelector('#ltoolinformation-info p').style.color = informationfontcolor;
                });
            }
        }
    }
    /**
     * Get course information Modal info.
     * @param {object} params
     * @return {string} course info html
     */
    function getCourseInfoModal(params) {
        return Fragment.loadFragment('ltool_information', 'get_courseinformation', params.contextid, params);
    }

    return {
        init: function(params) {
            learningToolinformationAction(params);
        }
    };
 });