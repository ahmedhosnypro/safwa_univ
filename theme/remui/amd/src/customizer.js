/* eslint-disable no-undef */
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
 * @module     theme_remui/customizer
 * @copyright  (c) 2023 WisdmLabs (https://wisdmlabs.com/)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Yogesh Shirsath
 */

import $ from "jquery";
import Ajax from "core/ajax";
import Notification from "core/notification";
import ModalFactory from "core/modal_factory";
import "core/modal_save_cancel";
import ModalEvents from "core/modal_events";
import Utils from "theme_remui/customizer/utils";
import globalSite from "theme_remui/customizer/global-site";
import globalBody from "theme_remui/customizer/global-body";
import globalColors from "theme_remui/customizer/global-colors";
import globalHeading from "theme_remui/customizer/global-heading";
import globalButtons from "theme_remui/customizer/global-buttons";
import headerLogo from "theme_remui/customizer/header-logo";
import headerSiteDesign from "theme_remui/customizer/header-design";
import footer from "theme_remui/customizer/footer";
import additionalSettings from "theme_remui/customizer/additional-settings";
import iconsettings from "theme_remui/customizer/icon-settings";
import quickSetup from "theme_remui/customizer/quicksetup-settings";
import login from "theme_remui/customizer/login";

/**
 * Ajax promise requests
 */
var PROMISES = {
    /**
     * Save settings to database
     * @param {Array} settings Settings string
     * @param {Object} options Additional options.
     * @return {Promise}
     */
    SAVE_SETTINGS: (settings, options) => {
        if (options == undefined) {
            options = {};
        }
        return Ajax.call([{
            methodname: "theme_remui_customizer_save_settings",
            args: {
                settings: JSON.stringify(settings),
                options: JSON.stringify(options)
            },
        }])[0];
    },
};

/**
 * Customizer panel settings handler.
 */
var handlers = [
    globalSite,
    globalBody,
    globalColors,
    globalHeading,
    globalButtons,
    headerLogo,
    headerSiteDesign,
    footer,
    additionalSettings,
    iconsettings,
    quickSetup,
    login,
];

/**
 * Selectors
 */
var SELECTOR = {
    CUSTOMIZER: "#customizer",
    CONTROLS: "#customize-controls",
    MODE_TOGGLE: "#customize-controls .mode-toggle",
    WRAP: "#customizer-wrap",
    CLOSE_CUSTOMIZER: ".customize-controls-close",
    CUSTMIZER_TOGGLE: ".customizer-controls-toggle",
    COLOR_SETTING: ".setting-type-color",
    PUBLISH: "#publish-settings",
    IFRAME: "#customizer-frame",
    MAIN_OVERLAY: "#main-overlay",
    PANEL_LINK: "[sidebar-panel-link]",
    PANEL_BACK: ".customize-panel-back",
    PANEL: ".sidebar-panel",
    PANEL_ID: "panel-id",
    PREVIOUS: "previous",
    CURRENT: "current",
    NEXT: "next",
    SETTINGS_RESET: "#reset-settings",
    INPUT_RESET: ".input-reset",
    SELECT_RESET: ".select-reset",
    CHECKBOX_RESET: ".checkbox-reset",
    COLOR_RESET: ".color-reset",
    TEXTAREA_RESET: ".textarea-reset",
    HTMLEDITOR_RESET: ".htmleditor-reset",
    HEADING_TOGGLE: ".heading-toggle",
    RANGEINPUT: ".form-range",
};

/**
 * Apply settings on iframe load.
 */
function applySettings() {
    handlers.forEach(handler => handler.apply());
    // Trigger apply so external js can handle customizer apply.
    $(document).trigger("edwiser.customizer.apply");
}

/**
 * Initialize setting change handler.
 */
function initHandlers() {
    handlers.forEach(handler => handler.init());
    // Trigger init so external js can handle customizer init.
    $(document).trigger("edwiser.customizer.init");
}

/**
 * Field reset handlers.
 */
function resetHandlers() {
    // Color reset.
    $(SELECTOR.COLOR_RESET).on("click", function() {
        let color = $(this).data("default");
        $(this).closest('.form-group').find("input").spectrum("set", color);
        $(this).closest('.form-group').find("input").trigger("color.changed", color);
    });

    // Checkbox reset.
    $(SELECTOR.CHECKBOX_RESET).on("click", function() {
        let value = $(this).data("default");
        $(this).closest('.form-group').find("input").prop(
            "checked",
            $(this).closest('.form-group').find("input").val() == value
        );
        $(this).closest('.form-group').find("input").trigger("change").trigger("input");
    });

    // Reset select.
    $(SELECTOR.SELECT_RESET).on("click", function() {
        let value = $(this).data("default");
        $(this).closest('.form-group').find("select").val(value).trigger("input").trigger("change");
    });

    // Reset input.
    $(SELECTOR.INPUT_RESET).on("click", function() {
        let value = $(this).data("default");
        $(this).closest('.form-group').find("input").val(value).trigger("input").trigger("change");
    });

    // Reset textarea.
    $(SELECTOR.TEXTAREA_RESET).on("click", function() {
        let value = $(this).data("default");
        $(this)
            .closest('.form-group')
            .find("textarea")
            .val(value)
            .trigger("input")
            .trigger("change");
    });

    // Reset htmleditor.
    $(SELECTOR.HTMLEDITOR_RESET).on("click", function() {
        let value = $(this).data("default");
        let textarea = $(this).closest('.form-group').find("textarea");
        $(this)
            .closest('.form-group')
            .find(`#${textarea.attr("id")}editable`)
            .html(value);
        textarea.val(value).trigger("input").trigger("change");
    });
}

/**
 * Handle page load and link change of iframe.
 * When loaded or link changed, reapplying all settings.
 */
function iframeHandler() {
    var contentDocument = Utils.getDocument();
    $(contentDocument).find("body").addClass("customizer-opened");
    $(contentDocument)
        .find(".customizer-editing-icon")
        .closest("a")
        .addClass("d-none")
        .removeClass("d-flex");
    $(contentDocument).find("#sidebar-setting").addClass("d-none");
    $(document).trigger("remui-adjust-left-side");
    var contentWindow = this.contentWindow;
    setTimeout(() => {
        // Change browser url on iframe navigation.
        window.history.replaceState(
            "pagechange",
            document.title,
            M.cfg.wwwroot +
            "/theme/remui/customizer.php?url=" +
            encodeURI(contentWindow.location.href)
        );

        // Set current iframe url to customizer close button.
        $(SELECTOR.CLOSE_CUSTOMIZER).attr("href", contentWindow.location.href);

        // Apply setting on iframe load.
        applySettings();

        // Hide overlay when iframe loaded.
        Utils.hideLoader();
        contentWindow.onbeforeunload = () => {
            // Show overlay when iframe loaded.
            Utils.showLoader();
        };
    }, 10);
}

/**
 * Reset all settings.
 * It also shows confirmation modal.
 */
function resetAllSettingHandler() {
    ModalFactory.create({
            title: M.util.get_string("reset", "moodle"),
            body: M.util.get_string("reset-settings-description", "theme_remui"),
            type: ModalFactory.types.SAVE_CANCEL,
        },
        $("#create")
    ).done(modal => {
        modal.show();
        modal.setSaveButtonText(M.util.get_string("yes", "moodle"));
        var root = modal.getRoot();
        root.on(ModalEvents.save, () => {
            $(SELECTOR.MAIN_OVERLAY).removeClass("d-none");
            PROMISES.SAVE_SETTINGS([{
                    name: "customcss",
                    value: $('[name="customcss"]').val()
                }], {
                    reset: true
                })
                .done(() => {
                    location.reload();
                })
                .fail(ex => {
                    Notification.exception(ex);
                    $(SELECTOR.MAIN_OVERLAY).addClass("d-none");
                });
        });
    });
}

/**
 * Publish changes to server.
 */
function publishChanges() {
    $(SELECTOR.MAIN_OVERLAY).removeClass("d-none");
    let settings = $(SELECTOR.CONTROLS).serializeArray();
    settings.forEach((element, index) => {
        if ($(`[name="${element.name}"]`).is(".site-colorpicker")) {
            element.value = $(`[name="${element.name}"]`)
                .spectrum("get")
                .toString();
            settings[index] = element;
        }
    });
    PROMISES.SAVE_SETTINGS(settings)
        .done(response => {
            let obj = {
                type: ModalFactory.types.ALERT,
            };
            if (response.status == false) {
                obj.title = M.util.get_string("error", "theme_remui");
                obj.body = response.errors;
            } else {
                obj.title = M.util.get_string("success", "moodle");
                obj.body = response.message;
                $(SELECTOR.CONTROLS).data("unsaved", false);
            }
            ModalFactory.create(obj, $("#create")).done(modal => {
                modal.show();
                $(SELECTOR.MAIN_OVERLAY).addClass("d-none");
            });
        })
        .fail(Notification.exception);
}

/**
 * Close customizer.
 * @param {DOMEvent} event Click event.
 * @returns {boolean}
 */
function closeCustomizer(event) {
    if ($(SELECTOR.CONTROLS).data("unsaved") == false) {
        return true;
    }
    event.preventDefault();
    ModalFactory.create({
            title: M.util.get_string("customizer-close-heading", "theme_remui"),
            body: M.util.get_string(
                "customizer-close-description",
                "theme_remui"
            ),
            type: ModalFactory.types.SAVE_CANCEL,
        },
        $("#create")
    ).done(modal => {
        modal.show();
        modal.setSaveButtonText(M.util.get_string("yes", "moodle"));
        var root = modal.getRoot();
        root.on(ModalEvents.save, () => {
            window.location = $(SELECTOR.CLOSE_CUSTOMIZER).attr("href");
        });
    });
    return true;
}

/**
 * Initialize events
 */
function init() {
    // Initialize customizer only once.
    if (window["customizer-enabled"] == true) {
        return;
    }
    window["customizer-enabled"] = true;

    $(() => {
        initHandlers();
        resetHandlers();

        // Iframe on load event.
        $(SELECTOR.IFRAME).on("load", iframeHandler);

        $(SELECTOR.SETTINGS_RESET).on("click", resetAllSettingHandler);

        // Open next panel.
        $(SELECTOR.PANEL_LINK).on("click", function() {
            $(SELECTOR.PANEL + "#" + $(this).data(SELECTOR.PANEL_ID)).addClass(
                SELECTOR.CURRENT
            );
            $(this).closest(SELECTOR.PANEL).removeClass(SELECTOR.CURRENT);
        });

        // Go back to previous panel.
        $(SELECTOR.PANEL_BACK).on("click", function() {
            $(
                SELECTOR.PANEL +
                ":not(" +
                SELECTOR.PANEL +
                "#" +
                $(this).data(SELECTOR.PANEL_ID) +
                ")"
            ).removeClass(SELECTOR.CURRENT);
            $(SELECTOR.PANEL + "#" + $(this).data(SELECTOR.PANEL_ID)).addClass(
                SELECTOR.CURRENT
            );
        });

        // Toggle screen mode.
        $(SELECTOR.MODE_TOGGLE).on("click", function() {
            $(SELECTOR.CUSTOMIZER)
                .removeClass("mode-desktop mode-tablet mode-mobile")
                .addClass(`mode-${$(this).data("mode")}`);
        });

        // Prevent submission.
        $(SELECTOR.CONTROLS).on("submit", function(event) {
            event.preventDefault();
            return false;
        });

        // Form change handler.
        $(`
            ${SELECTOR.CONTROLS} input[type="text"],
            ${SELECTOR.CONTROLS} input[type="number"],
            ${SELECTOR.CONTROLS} input[type="checkbox"]
            ${SELECTOR.CONTROLS} textarea,
            ${SELECTOR.CONTROLS} select
        `).on("change", function() {
            $(SELECTOR.CONTROLS).data("unsaved", true);
        });
        $(`${SELECTOR.CONTROLS} input[type="color"]`).on(
            "color.changed",
            function() {
                $(SELECTOR.CONTROLS).data("unsaved", true);
            }
        );

        // Submit settings to database.
        $(SELECTOR.PUBLISH).on("click", publishChanges);

        // Handle customizer close event.
        $(SELECTOR.CLOSE_CUSTOMIZER).on("click", closeCustomizer);

        // Toggle customizer.
        $(SELECTOR.CUSTMIZER_TOGGLE).on("click", function() {
            $("body").toggleClass("full-customizer");
        });

        // Toggle headings.
        $(SELECTOR.HEADING_TOGGLE).on("click", function() {
            $(this).toggleClass("collapsed");
            $(this).next().slideToggle("fast");
        });

        // Range slider observer.
        $("body").on("input", SELECTOR.RANGEINPUT, function() {
            let id = $(this).attr("id");
            let value = $(this).val();
            $(`#${id}-range-value`).text(value);
        });
    });
}

export {
    init
}
