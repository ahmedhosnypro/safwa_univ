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
 * Theme customizer global-heading js
 * @copyright (c) 2023 WisdmLabs (https://wisdmlabs.com/) <support@wisdmlabs.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Yogesh Shirsath
 */

import $ from 'jquery';
import Utils from 'theme_remui/customizer/utils';

/**
 * Headings list
 */
var headings = ['all', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'];

/**
 * Selectors
 */
var SELECTOR = {
    HEADING: 'typography-heading'
};

// Add heading in selector.
headings.forEach(function(heading) {
    SELECTOR['FONTFAMILY' + heading] = `[name="typography-heading-${heading}-fontfamily"]`;
    SELECTOR['FONTSIZE' + heading] = `typography-heading-${heading}-fontsize`;
    // SELECTOR['FONTWEIGHT' + heading] = `[name="typography-heading-${heading}-fontweight"]`;
    SELECTOR['TEXTTRANSFORM' + heading] = `[name="typography-heading-${heading}-text-transform"]`;
    SELECTOR['LINEHEIGHT' + heading] = `[name="typography-heading-${heading}-lineheight"]`;
    SELECTOR['CUSTOMCOLOR' + heading] = `[name="typography-heading-${heading}-custom-color"]`;
    SELECTOR['TEXTCOLOR' + heading] = `[name="typography-heading-${heading}-textcolor"]`;
});

/**
 * Get site heading content.
 * @param {string} heading Heading tag
 * @return {string} site color content
 */
function getContent(heading) {
    let fontSize;
    let fontFamily = $(SELECTOR['FONTFAMILY' + heading]).val();
    if (fontFamily.toLowerCase() == 'inherit') {
        fontFamily = $(SELECTOR.FONTFAMILYall).val();
    }

    let tags = [heading, '.' + heading];

    let num = heading.replace('h', '');
    ['regular', 'semibold', 'bold', 'exbold']
    .forEach(type => {
        tags.push(`.h-${type}-${num}`);
    });

    let tag = tags.join(', ');
    let content = '';

    if (fontFamily.toLowerCase() != 'inherit') {
        Utils.loadFont(fontFamily);
    }

    content += `\n
        ${tag} {
    `;

    if (fontFamily.toLowerCase() != 'inherit') {
        content += `\nfont-family: "${fontFamily}",sans-serif !important;`;
    }

    fontSize = $(`[name="${SELECTOR['FONTSIZE' + heading]}"]`).val();
    content += `\nfont-size: ${fontSize}rem;`;

    // let fontWeight = $(SELECTOR['FONTWEIGHT' + heading]).val();
    // if (fontWeight.toLowerCase() != 'inherit') {
    //     content += `\nfont-weight: ${fontWeight};`;
    // }

    let textTransform = $(SELECTOR['TEXTTRANSFORM' + heading]).val();
    if (textTransform.toLowerCase() == 'inherit') {
        textTransform = $(SELECTOR.TEXTTRANSFORMall).val();
    }
    if (textTransform.toLowerCase() != 'inherit') {
        content += `\ntext-transform: ${textTransform};`;
    }

    let lineHeight = $(SELECTOR['LINEHEIGHT' + heading]).val();
    if (lineHeight != '') {
        content += `\nline-height: ${lineHeight};`;
    }

    let customcolor = $(SELECTOR['CUSTOMCOLOR' + heading]).is(':checked');
    if (customcolor == true) {
        $(SELECTOR['TEXTCOLOR' + heading]).closest('.setting-item').slideDown(100);
    } else {
        $(SELECTOR['TEXTCOLOR' + heading]).closest('.setting-item').slideUp(100);
    }

    let textColor = $(SELECTOR.TEXTCOLORall).val();
    if (customcolor == true) {
        textColor = $(SELECTOR['TEXTCOLOR' + heading]).val();
    }

    content += `\ncolor: ${textColor} !important;
    }`;

    // Tablet.
    fontSize = $(`[name='${SELECTOR['FONTSIZE' + heading]}-tablet']`).val();
    if (fontSize != '') {
        content += `\n
            @media screen and (min-width: ${Utils.deviceWidth.sm + 1}px)
            and (max-width: ${Utils.deviceWidth.md}px) {
                ${tag} {
                    font-size: ${fontSize}rem;
                }
            }
        `;
    }
    return content;
}

/**
 * Apply settings.
 */
function apply() {
    headings.forEach(function(heading) {
        if (heading == 'all') {
            return;
        }
        Utils.putStyle(SELECTOR.HEADING + heading, getContent(heading));
    });
}

/**
 * Initialize events.
 */
function init() {
    var select = [];
    var color = [];
    headings.forEach(function(heading) {
        select.push(`
            ${SELECTOR['FONTFAMILY' + heading]},
            [name='${SELECTOR['FONTSIZE' + heading]}'],
            [name='${SELECTOR['FONTSIZE' + heading]}-tablet'],
            [name='${SELECTOR['FONTSIZE' + heading]}-mobile'],
            ${SELECTOR['TEXTTRANSFORM' + heading]},
            ${SELECTOR['LINEHEIGHT' + heading]},
            ${SELECTOR['CUSTOMCOLOR' + heading]}
        `);
        color.push(SELECTOR['TEXTCOLOR' + heading]);
    });

    $(select.join(', ')).on('input', function() {
        let heading = $(this).attr('name').split('-').splice(2, 1)[0];
        if (heading == 'all') {
            apply();
            return;
        }
        Utils.putStyle(SELECTOR.HEADING + heading, getContent(heading));
    });

    $(color.join(', ')).on('color.changed', function() {
        let heading = $(this).attr('name').split('-').splice(2, 1)[0];
        if (heading == 'all') {
            apply();
            return;
        }
        Utils.putStyle(SELECTOR.HEADING + heading, getContent(heading));
    });
}

export default {
    init,
    apply
};
