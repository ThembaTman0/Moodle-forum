<?php


defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/../../locallib.php');



class mod_moodleforum_generator extends testing_module_generator {

    protected $discussioncount = 0;


    protected $postcount = 0;


    public function reset() {
        $this->discussioncount = 0;
        $this->postcount = 0;

        parent::reset();
    }


    public function create_instance($record = null, array $options = null) {

        // Transform the record.
        $record = (object) (array) $record;

        if (!isset($record->name)) {
            $record->name = 'Test MO Instance';
        }
        if (!isset($record->intro)) {
            $record->intro = 'Test Intro';
        }
        if (!isset($record->introformat)) {
            $record->introformat = 1;
        }
        if (!isset($record->timecreated)) {
            $record->timecreated = time();
        }
        if (!isset($record->timemodified)) {
            $record->timemodified = time();
        }
        if (!isset($record->forcesubscribe)) {
            $record->forcesubscribe = moodleforum_CHOOSESUBSCRIBE;
        }

        return parent::create_instance($record, (array) $options);
    }


    public function create_discussion($record = null, $forum = null) {
        global $DB;

        // Increment the discussion count.
        $this->discussioncount++;

        // Create the record.
        $record = (array) $record;

        // Check needed submitted values.
        if (!isset($record['course'])) {
            throw new coding_exception('course must be present in phpunit_util:create_discussion() $record');
        }
        if (!isset($record['moodleforum'])) {
            throw new coding_exception('moodleforum must be present in phpunit_util:create_discussion() $record');
        }
        if (!isset($record['userid'])) {
            throw new coding_exception('userid must be present in phpunit_util:create_discussion() $record');
        }

        // Set default values.
        if (!isset($record['name'])) {
            $record['name'] = 'Discussion ' . $this->discussioncount;
        }
        if (!isset($record['message'])) {
            $record['message'] = html_writer::tag('p', 'Message for discussion ' . $this->discussioncount);
        }
        if (!isset($record['messageformat'])) {
            $record['messageformat'] = editors_get_preferred_format();
        }
        if (!isset($record['timestart'])) {
            $record['timestart'] = "0";
        }
        if (!isset($record['timeend'])) {
            $record['timeend'] = "0";
        }
        if (isset($record['mailed'])) {
            $mailed = $record['mailed'];
        }
        if (isset($record['timemodified'])) {
            $timemodified = $record['timemodified'];
        }
        $record['attachments'] = null;

        // Transform the array into an object.
        $record = (object) $record;

        // Get the module context.
        $cm = get_coursemodule_from_instance('moodleforum', $forum->id);

        $modulecontext = \context_module::instance($cm->id);

        // Add the discussion.
        $record->id = moodleforum_add_discussion($record, $modulecontext, $record->userid);

        if (isset($timemodified) || isset($mailed)) {
            $post = $DB->get_record('moodleforum_posts', array('discussion' => $record->id));

            if (isset($mailed)) {
                $post->mailed = $mailed;
            }

            if (isset($timemodified)) {
                $record->timemodified = $timemodified;
                $post->modified = $post->created = $timemodified;
                $DB->update_record('moodleforum_discussions', $record);
            }

            $DB->update_record('moodleforum_discussions', $record);
        }

        $discussion = $DB->get_record('moodleforum_discussions', array('id' => $record->id));

        // Return the discussion object.
        return $discussion;
    }

    public function create_post($record = null) {
        global $DB;
        // Increment the forum post count.
        $this->postcount++;
        // Variable to store time.
        $time = time() + $this->postcount;
        $record = (array) $record;
        if (!isset($record['discussion'])) {
            throw new coding_exception('discussion must be present in phpunit_util::create_post() $record');
        }
        if (!isset($record['userid'])) {
            throw new coding_exception('userid must be present in phpunit_util::create_post() $record');
        }
        if (!isset($record['parent'])) {
            $record['parent'] = 0;
        }
        if (!isset($record['message'])) {
            $record['message'] = html_writer::tag('p', 'Forum message post ' . $this->postcount);
        }
        if (!isset($record['created'])) {
            $record['created'] = $time;
        }
        if (!isset($record['modified'])) {
            $record['modified'] = $time;
        }
        if (!isset($record['mailed'])) {
            $record['mailed'] = 0;
        }
        if (!isset($record['messageformat'])) {
            $record['messageformat'] = 0;
        }
        if (!isset($record['attachment'])) {
            $record['attachment'] = "";
        }

        $record = (object) $record;
        // Add the post.
        $record->id = $DB->insert_record('moodleforum_posts', $record);

        // Update the last post.
        moodleforum_discussion_update_last_post($record->discussion);

        return $record;
    }

    /**
     * Function to create a dummy rating.
     *
     * @param array|stdClass $record
     *
     * @return stdClass the post object
     */
    public function create_rating($record = null) {
        global $DB;

        $time = time();
        $record = (array) $record;
        if (!isset($record['moodleforumid'])) {
            throw new coding_exception('moodleforumid must be present in phpunit_util::create_rating() $record');
        }
        if (!isset($record['discussionid'])) {
            throw new coding_exception('discussionid must be present in phpunit_util::create_rating() $record');
        }
        if (!isset($record['userid'])) {
            throw new coding_exception('userid must be present in phpunit_util::create_rating() $record');
        }
        if (!isset($record['postid'])) {
            throw new coding_exception('postid must be present in phpunit_util::create_rating() $record');
        }
        if (!isset($record['rating'])) {
            $record['rating'] = 1;
        }
        if (!isset($record['firstrated'])) {
            $record['firstrated'] = $time;
        }
        if (!isset($record['lastchanged'])) {
            $record['lastchanged'] = $time;
        }

        $record = (object) $record;
        // Add the post.
        $record->id = $DB->insert_record('moodleforum_ratings', $record);

        return $record;
    }


    /**
     * Create a new discussion and post within the specified forum, as the
     * specified author.
     *
     * @param stdClass $forum  The forum to post in
     * @param stdClass $author The author to post as
     * @param          array   An array containing the discussion object, and the post object
     */
    /**
     * Create a new discussion and post within the specified forum, as the
     * specified author.
     *
     * @param stdClass $forum   The moodleforum to post in
     * @param stdClass $author  The author to post as
     *
     * @return array The discussion and the post record.
     */
    public function post_to_forum($forum, $author) {
        global $DB;
        // Create a discussion in the forum, and then add a post to that discussion.
        $record = new stdClass();
        $record->course = $forum->course;
        $record->userid = $author->id;
        $record->moodleforum = $forum->id;
        $discussion = $this->create_discussion($record, $forum);
        // Retrieve the post which was created by create_discussion.
        $post = $DB->get_record('moodleforum_posts', array('discussion' => $discussion->id));

        return array($discussion, $post);
    }

    /**
     * Update the post time for the specified post by $factor.
     *
     * @param stdClass $post   The post to update
     * @param int      $factor The amount to update by
     */
    public function update_post_time($post, $factor) {
        global $DB;
        // Update the post to have a created in the past.
        $DB->set_field('moodleforum_posts', 'created', $post->created + $factor, array('id' => $post->id));
    }

    /**
     * Update the subscription time for the specified user/discussion by $factor.
     *
     * @param stdClass $user       The user to update
     * @param stdClass $discussion The discussion to update for this user
     * @param int      $factor     The amount to update by
     */
    public function update_subscription_time($user, $discussion, $factor) {
        global $DB;
        $sub = $DB->get_record('moodleforum_discuss_subs', array('userid' => $user->id, 'discussion' => $discussion->id));
        // Update the subscription to have a preference in the past.
        $DB->set_field('moodleforum_discuss_subs', 'preference', $sub->preference + $factor, array('id' => $sub->id));
    }

    /**
     * Create a new post within an existing discussion, as the specified author.
     *
     * @param stdClass $forum      The forum to post in
     * @param stdClass $discussion The discussion to post in
     * @param stdClass $author     The author to post as
     *
     * @return stdClass The forum post
     */
    public function post_to_discussion($forum, $discussion, $author) {
        // Add a post to the discussion.
        $record = new stdClass();
        $record->course = $forum->course;
        $record->userid = $author->id;
        $record->moodleforum = $forum->id;
        $record->discussion = $discussion->id;
        $post = $this->create_post($record);

        return $post;
    }

    /**
     * Create a new post within an existing discussion, as the specified author.
     *
     * @param stdClass $parent  The parent post
     * @param stdClass $author  The author to post as
     *
     * @return stdClass The new moodleforum post
     */
    public function reply_to_post($parent, $author) {
        // Add a post to the discussion.
        $record = (object) [
            'discussion' => $parent->discussion,
            'parent'     => $parent->id,
            'userid'     => $author->id
        ];
        $post = $this->create_post($record);

        return $post;
    }
}
