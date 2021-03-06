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
 * Prints a particular instance of moodleforum
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package   mod_moodleforum
 * @copyright 2017 Kennet Winter <k_wint10@uni-muenster.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Include config and locallib.
require_once(__DIR__.'/../../config.php');
require_once($CFG->dirroot.'/mod/moodleforum/locallib.php');

// Declare optional parameters.
$id      = optional_param('id', 0, PARAM_INT);       // Course Module ID.
$m       = optional_param('m', 0, PARAM_INT);        // moodleforum ID.
$page    = optional_param('page', 0, PARAM_INT);     // Which page to show.

// Set the parameters.
$params = array();
if ($id) {
    $params['id'] = $id;
} else {
    $params['m'] = $m;
}
if ($page) {
    $params['page'] = $page;
}
$PAGE->set_url('/mod/moodleforum/view.php', $params);

// Check for the course and module.
if ($id) {
    $cm              = get_coursemodule_from_id('moodleforum', $id, 0, false, MUST_EXIST);
    $course          = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $moodleforum  = $DB->get_record('moodleforum', array('id' => $cm->instance), '*', MUST_EXIST);
} else if ($m) {
    $moodleforum  = $DB->get_record('moodleforum', array('id' => $m), '*', MUST_EXIST);
    $course          = $DB->get_record('course', array('id' => $moodleforum->course), '*', MUST_EXIST);
    $cm              = get_coursemodule_from_instance('moodleforum', $moodleforum->id, $course->id, false, MUST_EXIST);
} else {
    print_error('missingparameter');
}



// Require a login.
require_login($course, true, $cm);

// Set the context.
$context = context_module::instance($cm->id);
$PAGE->set_context($context);

// Check some capabilities.
if (!has_capability('mod/moodleforum:viewdiscussion', $context)) {
    notice(get_string('noviewdiscussionspermission', 'moodleforum'));
}

// Mark the activity completed (if required) and trigger the course_module_viewed event.
$event = \mod_moodleforum\event\course_module_viewed::create(array(
    'objectid' => $PAGE->cm->instance,
    'context' => $PAGE->context,
));
$event->trigger();

// Print the page header.
$PAGE->set_url('/mod/moodleforum/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($moodleforum->name));
$PAGE->set_heading(format_string($course->fullname));

// Output starts here.
echo $OUTPUT->header();

// Show the name of the instance.
echo $OUTPUT->heading(format_string($moodleforum->name), 2);

// Show the description of the instance.
if (!empty($moodleforum->intro)) {
    echo $OUTPUT->box(format_module_intro('moodleforum', $moodleforum, $cm->id), 'generalbox', 'intro');
}

// Return here after posting, etc.
$SESSION->fromdiscussion = qualified_me();

// Print the discussions.
echo '<br />';
moodleforum_print_latest_discussions($moodleforum, $cm, $page, get_config('moodleforum', 'manydiscussions'));

// Finish the page.
echo $OUTPUT->footer();
