<?php
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
 * Definition of log events
 *
 * @package    mod
 * @subpackage peerreview
 * @copyright  2013 Michael de Raadt (michaeld@moodle.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $DB;

$logs = array(
    array('module' => 'peerreview', 'action' => 'add',           'mtable' => 'peerreview', 'field' => 'name'),
    array('module' => 'peerreview', 'action' => 'update',        'mtable' => 'peerreview', 'field' => 'name'),
    array('module' => 'peerreview', 'action' => 'view',          'mtable' => 'peerreview', 'field' => 'name'),
    array('module' => 'peerreview', 'action' => 'submit',        'mtable' => 'peerreview', 'field' => 'name'),
    array('module' => 'peerreview', 'action' => 'review',        'mtable' => 'peerreview', 'field' => 'name'),
    array('module' => 'peerreview', 'action' => 'view feedback', 'mtable' => 'peerreview', 'field' => 'name'),
);
