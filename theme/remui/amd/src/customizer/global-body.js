/* eslint-disable no-console */
/*eslint-disable no-unused-vars*/
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
 * Theme customizer global-body js
 * @copyright (c) 2023 WisdmLabs (https://wisdmlabs.com/) <support@wisdmlabs.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Yogesh Shirsath
 */

import $ from 'jquery';
import Utils from 'theme_remui/customizer/utils';

var SELECTOR = {
    BASE: 'global-typography-body',
    FONTFAMILY: '[name="global-typography-body-fontfamily"]',
    FONTSIZE: 'global-typography-body-fontsize',
    FONTWEIGHT: '[name="global-typography-body-fontweight"]',
    LINEHEIGHT: '[name="global-typography-body-lineheight"]',
    TEXTTRANSFORM: '[name="global-typography-body-text-transform"]',
    LINK: '[name="enablebodysettingslinking"]',
    SMALLPARAFONTFAMILY: '[name="global-typography-smallpara-fontfamily"]',
    SMALLPARAFONTSIZE: 'global-typography-smallpara-fontsize',
    SMALLPARALINEHEIGHT: '[name="global-typography-smallpara-lineheight"]',
    SMALLPARATEXTTRANSFORM: '[name="global-typography-smallpara-text-transform"]',
    SMALLINFOFONTFAMILY: '[name="global-typography-smallinfo-fontfamily"]',
    SMALLINFOFONTSIZE: 'global-typography-smallinfo-fontsize',
    SMALLINFOLINEHEIGHT: '[name="global-typography-smallinfo-lineheight"]',
    SMALLINFOTEXTTRANSFORM: '[name="global-typography-smallinfo-text-transform"]',
    LAYOUT: '[name="pagewidth"]'
};

// Check whether settings are linked.
let isLinked = () => $(SELECTOR.LINK).is(':checked');

/**
 * Get global font name.
 * @return {String} Font name
 */
function getGlobalFont() {
    let fontFamily = $(SELECTOR.FONTFAMILY).val();
    if (fontFamily.toLocaleLowerCase() == 'standard') {
        fontFamily = 'Inter';
    }
    if (fontFamily.toLocaleLowerCase() == 'inherit') {
        // eslint-disable-next-line no-undef
        if (remuiFontSelect == 1) {
            return 'Inter';
        }
        // eslint-disable-next-line no-undef
        if (remuiFontName == '') {
            return 'Inter';
        }
        // eslint-disable-next-line no-undef
        return remuiFontName;
    }
    return fontFamily;
}

/**
 * Load font.
 * @param {string} font Font name
 */
function handleFont(font) {
    if (font.toLocaleLowerCase() == 'inherit') {
        return;
    }
    Utils.loadFont(font);
}

/**
 * Get body settings values.
 * @returns {object} Settings
 */
function getBodySettings() {
    return {
        'fontFamily': getGlobalFont(),
        'fontSize': {
            'default': $(`[name='${SELECTOR.FONTSIZE}']`).val(),
            'tablet': $(`[name='${SELECTOR.FONTSIZE}-tablet']`).val(),
            'mobile': $(`[name='${SELECTOR.FONTSIZE}-mobile']`).val()
        },
        'fontWeight': $(SELECTOR.FONTWEIGHT).val(),
        'textTransform': $(SELECTOR.TEXTTRANSFORM).val(),
        'lineHeight': $(SELECTOR.LINEHEIGHT).val()
    }
}

/**
 * Handle site body content.
 */
function handleBody() {
    let {
        fontFamily,
        fontSize,
        fontWeight,
        textTransform,
        lineHeight
    } = getBodySettings();

    if (isLinked()) {
        updateLinkedSettings();
    }
    let content = `
        body {
            font-size: ${fontSize.default}px !important;
            text-transform: ${textTransform} !important;
            font-family: "${fontFamily}",sans-serif !important;
            line-height: ${lineHeight} !important;
            font-weight: ${fontWeight} !important;
        }
    `;

    // Tablet.
    if (fontSize.tablet != '') {
        content += `\n
            @media screen and (min-width: ${Utils.deviceWidth.sm + 1}px) and (max-width: ${Utils.deviceWidth.md}px) {
                body {
                    font-size: ${fontSize.tablet}px !important;
                }
            }
        `;
    }

    // Mobile.
    if (fontSize.mobile != '') {
        content += `\n
            @media screen and (max-width: ${Utils.deviceWidth.sm}px) {
                body {
                    font-size: ${fontSize.mobile}px !important;
                }
            }
        `;
    }
    Utils.putStyle('global-body', content);
    handleFont(fontFamily);
}

/**
 * Handle small info text content.
 */
function handleSmallParagraph() {
    let fontFamily, fontSize, textTransform, lineHeight;
    fontFamily = $(SELECTOR.SMALLPARAFONTFAMILY).val();
    if (fontFamily.toLowerCase() == 'standard') {
        fontFamily = 'Inter';
    }
    fontSize = {
        'default': $(`[name='${SELECTOR.SMALLPARAFONTSIZE}']`).val(),
        'tablet': $(`[name='${SELECTOR.SMALLPARAFONTSIZE}-tablet']`).val(),
        'mobile': $(`[name='${SELECTOR.SMALLPARAFONTSIZE}-mobile']`).val()
    };
    textTransform = $(SELECTOR.SMALLPARATEXTTRANSFORM).val();
    lineHeight = $(SELECTOR.SMALLPARALINEHEIGHT).val();
    handleFont(fontFamily);
    let content = `
        .para-regular-2,
        .para-semibold-2,
        .para-underline-2 {
            font-family: ${fontFamily} !important;
            font-size: ${fontSize.default}px !important;
            text-transform: ${textTransform} !important;
            line-height: ${lineHeight} !important;
        }
    `;

    // Tablet.
    if (fontSize.tablet != '') {
        content += `\n
            @media screen and (min-width: ${Utils.deviceWidth.sm + 1}px) and (max-width: ${Utils.deviceWidth.md}px) {
                .para-regular-2,
                .para-semibold-2,
                .para-underline-2 {
                    font-size: ${fontSize.tablet}px !important;
                }
            }
        `;
    }

    // Mobile.
    if (fontSize.mobile != '') {
        content += `\n
            @media screen and (max-width: ${Utils.deviceWidth.sm}px) {
                .para-regular-2,
                .para-semibold-2,
                .para-underline-2 {
                    font-size: ${fontSize.mobile}px !important;
                }
            }
        `;
    }
    Utils.putStyle('global-small-paragraph', content);
}

/**
 * Handle small paragraph content.
 */
function handleSmallInfoText() {
    let fontFamily, fontSize, textTransform, lineHeight;
    fontFamily = $(SELECTOR.SMALLINFOFONTFAMILY).val();
    if (fontFamily.toLowerCase() == 'standard') {
        fontFamily = 'Inter';
    }
    fontSize = {
        'default': $(`[name='${SELECTOR.SMALLINFOFONTSIZE}']`).val(),
        'tablet': $(`[name='${SELECTOR.SMALLINFOFONTSIZE}-tablet']`).val(),
        'mobile': $(`[name='${SELECTOR.SMALLINFOFONTSIZE}-mobile']`).val()
    };
    textTransform = $(SELECTOR.SMALLINFOTEXTTRANSFORM).val();
    lineHeight = $(SELECTOR.SMALLINFOLINEHEIGHT).val();
    handleFont(fontFamily);

    let content = `
        .small-info-regular,
        .small-info-semibold,
        #page-header #page-navbar nav ol.breadcrumb .breadcrumb-item,
        .edw-msg-panel-badge,
        .message-app .view-conversation .content-message-container [data-region="day-messages-container"] [data-region="text-container"] p {
            font-family: ${fontFamily} !important;
            font-size: ${fontSize.default}px !important;
            text-transform: ${textTransform} !important;
            line-height: ${lineHeight} !important;
        }
    `;

    // Tablet.
    if (fontSize.tablet != '') {
        content += `\n
            @media screen and (min-width: ${Utils.deviceWidth.sm + 1}px) and (max-width: ${Utils.deviceWidth.md}px) {
                .small-info-regular,
                .small-info-semibold {
                    font-size: ${fontSize.tablet}px !important;
                }
            }
        `;
    }

    // Mobile.
    if (fontSize.mobile != '') {
        content += `\n
            @media screen and (max-width: ${Utils.deviceWidth.sm}px) {
                .small-info-regular,
                .small-info-semibold {
                    font-size: ${fontSize.mobile}px !important;
                }
            }
        `;
    }
    Utils.putStyle('global-small-info', content);
}

/**
 * Update linked settings.
 */
function updateLinkedSettings() {
    // Small paragraph.
    $(`[name="${SELECTOR.SMALLPARAFONTSIZE}"]`).val($(`[name="${SELECTOR.FONTSIZE}"]`).val() - 2);
    $(`[name="${SELECTOR.SMALLPARAFONTSIZE}-tablet"]`).val($(`[name="${SELECTOR.FONTSIZE}-tablet"]`).val() - 2);
    $(`[name="${SELECTOR.SMALLPARAFONTSIZE}-mobile"]`).val($(`[name="${SELECTOR.FONTSIZE}-mobile"]`).val() - 2);
    $(SELECTOR.SMALLPARAFONTFAMILY).val('inherit');
    $(SELECTOR.SMALLPARALINEHEIGHT).val($(SELECTOR.LINEHEIGHT).val());
    $(SELECTOR.SMALLPARATEXTTRANSFORM).val('inherit');
    handleSmallParagraph();

    // Small info text.
    $(`[name="${SELECTOR.SMALLINFOFONTSIZE}"]`).val($(`[name="${SELECTOR.FONTSIZE}"]`).val() - 4);
    $(`[name="${SELECTOR.SMALLINFOFONTSIZE}-tablet"]`).val($(`[name="${SELECTOR.FONTSIZE}-tablet"]`).val() - 4);
    $(`[name="${SELECTOR.SMALLINFOFONTSIZE}-mobile"]`).val($(`[name="${SELECTOR.FONTSIZE}-mobile"]`).val() - 4);
    $(SELECTOR.SMALLINFOFONTFAMILY).val('inherit');
    $(SELECTOR.SMALLINFOLINEHEIGHT).val($(SELECTOR.LINEHEIGHT).val());
    $(SELECTOR.SMALLINFOTEXTTRANSFORM).val('inherit');
    handleSmallInfoText();
}

/**
 * Handling settings linking.
 */
function handleLinking() {
    $(`
        [name*="${SELECTOR.SMALLPARAFONTSIZE}"],
        [name*="${SELECTOR.SMALLINFOFONTSIZE}"],
        ${SELECTOR.SMALLPARAFONTFAMILY},
        ${SELECTOR.SMALLPARALINEHEIGHT},
        ${SELECTOR.SMALLPARATEXTTRANSFORM},
        ${SELECTOR.SMALLINFOFONTFAMILY},
        ${SELECTOR.SMALLINFOLINEHEIGHT},
        ${SELECTOR.SMALLINFOTEXTTRANSFORM}
    `).closest('.form-group').toggleClass('linked-setting', isLinked())
    .attr('title', isLinked() ? M.util.get_string('bodysettingslinked', 'theme_remui') : '');
    if (isLinked()) {
        updateLinkedSettings();
    }
}

/**
 * Handle page layout.
 */
function handleLayout() {
    let body = $(Utils.getDocument()).find('body');
    if (body.is('#page-site-index')) {
        body.removeClass('limitedwidth');
        return;
    }
    $(body).toggleClass('limitedwidth', $(SELECTOR.LAYOUT).val() == 'default');
}

/**
 * Apply settings.
 */
function apply() {
    handleBody();
    handleSmallParagraph();
    handleSmallInfoText();
    handleLayout();
}

/**
 * Initialize events.
 */
function init() {
    $(`
        [name='${SELECTOR.FONTSIZE}'],
        [name='${SELECTOR.FONTSIZE}-tablet'],
        [name='${SELECTOR.FONTSIZE}-mobile']
    `).on('input', handleBody);

    $(`
        ${SELECTOR.FONTWEIGHT},
        ${SELECTOR.TEXTTRANSFORM},
        ${SELECTOR.LINEHEIGHT},
        ${SELECTOR.FONTFAMILY}
    `).on('input', handleBody);

    // Handle small paragraph settings.
    $(`
        [name='${SELECTOR.SMALLPARAFONTSIZE}'],
        [name='${SELECTOR.SMALLPARAFONTSIZE}-tablet'],
        [name='${SELECTOR.SMALLPARAFONTSIZE}-mobile'],
        ${SELECTOR.SMALLPARATEXTTRANSFORM},
        ${SELECTOR.SMALLPARALINEHEIGHT},
        ${SELECTOR.SMALLPARAFONTFAMILY}
    `).on('input', handleSmallParagraph);

    // Handle small info text settings.
    $(`
        [name='${SELECTOR.SMALLINFOFONTSIZE}'],
        [name='${SELECTOR.SMALLINFOFONTSIZE}-tablet'],
        [name='${SELECTOR.SMALLINFOFONTSIZE}-mobile'],
        ${SELECTOR.SMALLINFOTEXTTRANSFORM},
        ${SELECTOR.SMALLINFOLINEHEIGHT},
        ${SELECTOR.SMALLINFOFONTFAMILY}
    `).on('input', handleSmallInfoText);

    // Handle link setting switch.
    $(SELECTOR.LINK).on('input', handleLinking);

    // Handle layout.
    $(SELECTOR.LAYOUT).on('change', handleLayout);

    handleLinking();
}

export default {
    init,
    apply
};
