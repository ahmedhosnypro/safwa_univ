<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Plugin version and other meta-data are defined here.
 *
 * @package     block_edutechpreferences
 * @copyright   2022 EduTech
 * @author      2022 Ricardo Emmanuel Reyes Acosta<ricardo.ra@aguascalientes.tecnm.mx>
 * @author      2022 Ricardo Mendoza Gonzalez<mendozagric@aguascalientes.tecnm.mx>
 * @author      2022 Mario Alberto Rodriguez Diaz<mario.rd@aguascalientes.tecnm.mx>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_edutechpreferences\translate;

class translate {
    /**
     * Check the language in the SESSION or of the USER if lang in SESSION is not set,
     * uses the function block_edutechpreferences_translations to get the translation if is necessary
     * @param string $preference
     * @return string $preferencetrans
     */
    public function block_edutechpreferences_translator($preference) {
        global $SESSION;
        global $USER;
        if (isset($SESSION->lang)) {
            $preferencetrans = substr($SESSION->lang, 0, 2) === 'es'
            ? $preference
            : $this->block_edutechpreferences_translations($preference);
        } else {
            $preferencetrans = substr($USER->lang, 0, 2) === 'es'
            ? $preference
            : $this->block_edutechpreferences_translations($preference);
        }
        return $preferencetrans;
    }
    /**
     * Translates the preference
     * @param string $preference
     * @return string $translation
     */
    public function block_edutechpreferences_translations($preference) {
        switch ($preference){
            case 'Recursos Digitales Visuales':
                $translate = 'Visual Digital Resources';
                break;
            case 'Descripción de texto para imágenes':
                $translate = 'Text description for images';
                break;
            case 'Evitar recursos con dependencia de color':
                $translate = 'Avoid resources with color dependence';
                break;
            case 'Información mayoritariamente visual':
                $translate = 'Mostly visual information';
                break;
            case 'Recursos Digitales Auditivos':
                $translate = 'Sounds Digital Resources';
                break;
            case 'información mayoritariamente auditivo':
                $translate = 'Mostly audible information';
                break;
            case 'Evitar sonido de fondo sin control':
                $translate = 'Avoid uncontrolled background sound';
                break;
            case 'Transcripción para audio y video':
                $translate = 'Transcription for audio and video';
                break;
            case 'Descripción de audio para video':
                $translate = 'Audio description for video';
                break;
            case 'Subtítulos para audio y video':
                $translate = 'Subtitles for audio and video';
                break;
            case 'Recursos Digitales Textuales':
                $translate = 'Textual Digital Resources';
                break;
            case 'Información mayoritariamente textual':
                $translate = 'Mostly textual information';
                break;
            case 'Nivel De Interactividad':
                $translate = 'Level Of Interactivity';
                break;
            case 'Manejo total con mouse':
                $translate = 'Full mouse operation';
                break;
            case 'Manejo total con teclado':
                $translate = 'Full keyboard operation';
                break;
            case 'Evitar simulación/movimiento':
                $translate = 'Avoid simulation/motion';
                break;
            case 'Evitar luces parpadeantes':
                $translate = 'Avoid flickering lights';
                break;
        }
        return $translate;
    }
}
