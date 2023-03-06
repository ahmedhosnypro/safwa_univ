/* eslint-disable no-console */
/* eslint-disable jsdoc/require-jsdoc*/
/* eslint-disable jsdoc/require-jsdoc*/
/* eslint-disable jsdoc/require-jsdoc*/
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
 * @module     theme_remui/remuiloader
 * @copyright (c) 2020 WisdmLabs (https://wisdmlabs.com/)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery'], function($) {

    const registerCommonEvents = () => {

        // Make AddBlock modal full Width.
        $('[data-key="addblock"]').click(function() {
            setTimeout(function() {
                $('.modal[data-region="modal-container"]').addClass("fullwidth-modal");
            }, 500);
        });


        // This events handle the collapse issue on the messaging panel.
        msgBodyViewFavourite.on('click', function(e) {
            stopCollapseInMsgPanel(msgBodyViewFavourite, e);
        });

        msgBodyViewGroupmsg.on('click', function(e) {
            stopCollapseInMsgPanel(msgBodyViewGroupmsg, e);
        });

        msgBodyViewPrivate.on('click', function(e) {
            stopCollapseInMsgPanel(msgBodyViewPrivate, e);
        });

        // This events handle the collapse issue on the messaging page .
        msgPanelBodyViewFavourite.on('click', function(e) {
            stopCollapseInMsgPanel(msgPanelBodyViewFavourite, e);
        });

        msgPanelBodyViewGroupmsg.on('click', function(e) {
            stopCollapseInMsgPanel(msgPanelBodyViewGroupmsg, e);
        });

        msgPanelBodyViewPrivate.on('click', function(e) {
            stopCollapseInMsgPanel(msgPanelBodyViewPrivate, e);
        });

        $(".nav-grouping-selector ul li ").on("click", function() {
            $(this).siblings("li").find("a").removeAttr("aria-current", false);
            $(this).find("a").attr("aria-current", true);
        });

        $(".nav-display-selector ul li ").on("click", function() {
            $(this).siblings("li").find("a").removeAttr("aria-current", false);
            $(this).siblings("li").find("span").removeAttr("aria-current", false);
            $(this).find("a").attr("aria-current", true);
            $(this).find("span").attr("aria-current", true);
        });

        registerGoToTopEvents();

        registerShowPasswordAction();
    };

    // Variables for msg body container
    var msgBodyViewFavourite = $('[data-region="body-container"]').find('[data-region="view-overview-favourites"]');
    var msgBodyViewGroupmsg = $('[data-region="body-container"]').find('[data-region="view-overview-group-messages"]');
    var msgBodyViewPrivate = $('[data-region="body-container"]').find('[data-region="view-overview-messages"]');

    // Variables for msg panel body container(messaging page)
    var msgPanelBodyViewFavourite = $('[data-region="panel-body-container"]').find('[data-region="view-overview-favourites"]');
    var msgPanelBodyViewGroupmsg = $('[data-region="panel-body-container"]').find('[data-region="view-overview-group-messages"]');
    var msgPanelBodyViewPrivate = $('[data-region="panel-body-container"]').find('[data-region="view-overview-messages"]');

    /*
     * Common method to stop collapsing messaging tabs
     */
    const stopCollapseInMsgPanel = ($this, e) => {
        if ($this.hasClass('expanded')) {
            e.preventDefault();
            e.stopPropagation();
        }
    };
    /*
     * Creating mini version of bootstrap-select for Dropdowns
     * {String} dropdownselector class selector for dropdown
     */
    const generateDropdownSearch = (dropdownselector) => {

        var _catmenus = $(dropdownselector + '+ .dropdown-menu');
        var _searchfield = ".catsearch";

        var sr_text = M.util.get_string('searchcatplaceholdertext', 'theme_remui');
        var _inputfield = '<div class="simplesearchform edw-search-field catsearchfield p-3">';
        _inputfield += '<div class="input-group d-flex align-items-center">';
        _inputfield += '<label for="header-category-searchinput">';
        _inputfield += '<span class="sr-only">Search</span>';
        _inputfield += '</label>';
        _inputfield += '<div class="input-group-prepend">';
        _inputfield += '<button type="submit" class="btn btn-submit" data-action="submit">';
        _inputfield += '<span class="edw-icon edw-icon-Search"></span>';
        _inputfield += '<span class="sr-only">' + sr_text + '</span>';
        _inputfield += '</button>';
        _inputfield += '</div>';
        _inputfield += '<input tabindex="1" type="text" id="header-category-searchinput" class="catsearch form-control withclear navigation-text" placeholder="' + sr_text + '" aria-label="' + sr_text + '" name="catsearch">';
        _inputfield += '</div>';

        _catmenus.prepend(_inputfield);
        $(".catsearch-item").unbind();
        $(".catsearch-item .catsearch").unbind();
        /*
         * Search Function in the dropdown items according to input field text.
         */
        function filterFunction() {
          var input, filter, a, i;
          input = $('.catselector-menu + .dropdown-menu').find(".catsearch");

          filter = input.val().toUpperCase();

          a = _catmenus.find("a");
          for (i = 0; i < a.length; i++) {
            var _txtval = a[i].textContent || a[i].innerText;
            _txtval = _txtval.split(" ").join("");
            if (_txtval.toUpperCase().indexOf(filter) > -1) {
              a[i].style.display = "";
            } else {
              a[i].style.display = "none";
            }
          }
        }
        _catmenus.find(_searchfield).on("keyup", function(e) {
            if (e.which == 32) {
                return true;
            }
            filterFunction();
        });

    };

    const registerGoToTopEvents = () => {
        const _GOTOPSELECTOR = "#gotop";
        // Scroll to top.
        $(_GOTOPSELECTOR).click(function() {
            $('html, body').animate({scrollTop: 0}, $(window).scrollTop() / 6);
            return false;
        });

        // Hide and Show Go to top button.
        $(window).scroll(function() {
            if ($(this).scrollTop() > 300) {
                $(_GOTOPSELECTOR).removeClass("d-none").addClass("d-flex");
            } else {
                $(_GOTOPSELECTOR).removeClass("d-flex").addClass("d-none");
            }
        });
    };

    const registerShowPasswordAction = () => {
        const _passwordfieldwrapper = '.password-field-eye';
        const _passwordShowIcon = _passwordfieldwrapper + ' .show-password-icon';
        const _passwordInputField = _passwordfieldwrapper + ' input[name="password"]';

        $(_passwordShowIcon).hover(
           function() {
               $(_passwordInputField).attr("type", "text");
           },
           function() {
               $(_passwordInputField).attr("type", "password");
           }
        );
    };

    return {
        init: function() {
            registerCommonEvents();

            // Enable Category Search filter in header.
            if ($(".catselector-menu").length) {
                generateDropdownSearch(".catselector-menu");
            }
        }
    };
});
