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
 * English strings for peerreviews.
 *
 * @package    mod
 * @subpackage peerreview
 * @copyright  2013 Michael de Raadt (michaeld@moodle.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['grade'] = 'Grade';

// Required strings.
$string['modulename'] = 'Peer review';
$string['modulename_help'] = 'Use the Peer review module to allow users to peer review other students\' submissions';
$string['modulenameplural'] = 'Peer review activities';
$string['pluginadministration'] = 'Peer review administration';
$string['pluginname'] = 'Peer review';

// Headings.
$string['criteria'] = 'Criteria';
$string['submissions'] = 'Submissions';

// Edit form strings.
$string['allowsubmissionsfromdate'] = 'Allow submissions from';
$string['allowsubmissionsfromdate_help'] = 'If enabled, students will not be able to submit before this date. If disabled, students will be able to start submitting right away.';
$string['cutoffdate'] = 'Cut-off date';
$string['cutoffdate_help'] = 'If set, submissions will not accepted after this date.';
$string['duedate'] = 'Due date';
$string['duedate_help'] = 'This is when the submission is due. Submissions will still be allowed after this date but any submissions after this date are marked as late. To prevent submissions after a certain date, set the cut off date.';
$string['fileextension'] = 'File extension';
$string['maximumsize'] = 'File size limit';
$string['peerreview'] = 'Peer review';
$string['peerreviewname'] = 'Peer review name';
$string['peerreviewname_help'] = 'This is the name that will appear in your course page for this activity.';
$string['peerreviewfieldset'] = 'Custom example fieldset';
$string['submissiondrafts'] = 'Staged submission';
$string['submissiondrafts_help'] = 'If enabled, students will be able to upload their file or write their online text, save it and then submit it in separate steps.';
$string['submissionformat'] = 'Submission format';
$string['submissionformatdocument'] = 'Submitted document';
$string['submissionformatonlinetext'] = 'Online text';
$string['valueofreview'] = 'Reward value';
$string['valueofreview_help'] = '
<p>Reviewing is a learning activity, and as such, it can be rewarded as part of the activity with marks.</p>
<p>Students conduct two reviews each.</p>
<p>The reward for conducting each of the two reviews can be valued from zero marks to half of the activity grade.
Normally each review would be rewarded with 10% of the total grade, with the remaining marks awarded for meeting the criteria.</p>
';

// Edit form validation strings.
$string['cutoffdatevalidation'] = 'Cut-off date must be after the due date.';
$string['cutoffdatefromdatevalidation'] = 'Cut-off date must be after the allow submissions from date.';
$string['duedatevalidation'] = 'Due date must be after the allow submissions from date.';
$string['rewardvalidation'] = 'As students complete two reviews, the reward for each cannot exceed half of the total grade.';

// Capability strings.
$string['peerreview:addinstance'] = 'Add a Peer review activity';
$string['peerreview:grade'] = 'Grade a Peer review activity';
$string['peerreview:revealidentities'] = 'See the identity of anonymous submitters and reviewers';
$string['peerreview:view'] = 'View a Peer review activity';
$string['value'] = 'Value';
$string['peerreview:submit'] = 'Submit a Peer review activity';
// Criteria page strings.
$string['criteriawriting'] = 'Writing criteria';
$string['criteriawriting_help'] = '
<p><em>Well written criteria are the key to a successful Peer review activity.</em></p>
<p><strong>Criteria should be...</strong></p>
<ul>
<li><strong>Objective</strong><br />
Criteria should be <em>objective</em> rather than <em>subjective</em>. They should be used to test the presence or correctness of a feature in a student\'s submission. They should not be used to ask students to make a judgement of quality.</li>
<br />
<li><strong>Concise</strong><br />
Students should be able to evaluate the work of another student, even if they themselves have not reached that standard. This means you should provide instructions on how to test the criteria.</li>
<br />
<li><strong>Binary</strong><br />
<em>Remember, these criteria are for students, not teachers.</em> If at first thought a scale is desired, ask yourself how you would define the levels of that scale; ask "what differentiates the levels?" Then ask: "what level is desirable?" Setting a binary criterion at the desired level drives students to accomplish that level. If you want to offer reward for a partially correct response, or a superior response, divide that criterion into finer grained criteria that are more specific. Binary criteria are makes objective evaluation possible for students.</li>
</ul>
<p><strong>Hidden Answers</strong></p>
<p>The criteria are shown to students with the activity description, even before they submit.</p>
<p>An optional additional alternate text can be provided for each criterion. This second text appears during and after reviewing. This allows you to create criteria with specific answers or tests that are not shown to the students before they submit.</p>
<p><strong>More Complex Criteria Text</strong></p>
<p>Criteria can be simple short pieces of text, or more complex. If you know how to write in HTML, this can help you to create criteria with structure and formatting. For example, tags such as <code>&lt;strong&gt;...&lt;/strong&gt;</code> can be placed around text to make it <strong>strong</strong>, <code>&lt;em&gt;...&lt;/em&gt;</code> tags for <em>emphasis</em>, a singleton <code>&lt;br /&gt;</code> tag can be used to include a line break, and so on.</p>
<p><strong>Criterion Values</strong></p>
<p>The values for all criteria, plus the reward students receive for completing reviews, should sum to the Grade value for the activity. The system attempts to enforce correct addition. Guidance is provided at the bottom of the Criteria page.</p>
<h6>Examples of Criteria</h6>
<p>The following are good examples are objective, concise, binary criteria. (HTML tags have been used to bolden text and insert line-breaks.)</p>
<table>
    <tr>
        <td colspan="2" style="border-top:1px solid #cccccc;"><em>Example from a programming activity...</em></td>
    </tr>
    <tr>
        <td style="vertical-align:top;"><input type="checkbox" checked disabled /></td>
        <td><strong>Comments are used to describe the purpose of blocks of code</strong><br />There should be at least three comments. Comments should describe blocks of code with a common purpose, eg., <pre>// Outputting a list with user stats</pre></td>
    </tr>
    <tr>
        <td colspan="2" style="border-top:1px solid #cccccc;"><em>Example from a word processing activity...</em></td>
    </tr>
    <tr>
        <td style="vertical-align:top;"><input type="checkbox" checked disabled /></td>
        <td><strong>An automatic table of contents is used</strong><br />The table of contents should have been created automatically, based on heading style headings, rather than entered manually. If you click in the table of contents, the entire table should be selected. You should not be able to edit the table of contents manually.</td>
    </tr>
    <tr>
        <td colspan="2" style="border-top:1px solid #cccccc;"><em>Example from an essay activity...</em></td>
    </tr>
    <tr>
        <td style="vertical-align:top;"><input type="checkbox" checked disabled /></td>
        <td><strong>Citations follow the IEEE referencing style</strong><br />Citations should be formatted in the IEEE style with a number in square brackets, for example <em>"...conclusively demonstrated [4]."</em>. Where a quote is presented, the page number should also be shown.</td>
    </tr>
</table>
';
$string['criterion'] = 'Criterion'; // Singular.
$string['citerionatreview'] = 'Shown at review (optional)';
$string['citerionwithdescription'] = 'Shown with description';
$string['valueofcriterion'] = 'Value of this criterion';
$string['valueofcriteria'] = 'Total value of criteria above';
$string['valueofreview'] = 'Reward value for completing each of the two reviews';
$string['valueofreview_help'] = '
<p>Reviewing is a learning activity, and as such, it can be rewarded as part of the grade with marks.</p>
<p>Students conduct two reviews each.</p>
<p>The reward for conducting each of the two reviews can be valued from zero marks to half of the grade.
Normally each review would be rewarded with 10% of the final grade, with the remaining marks awarded for meeting the set criteria.</p>
';
$string['addtwomorecriteria'] = 'Add 2 more criteria';
$string['marksummary'] = 'Mark Summary';
$string['marksummary_help'] = '
<p>Marks are awarded for completing reviews and also for achieving set criteria. The total of these needs to sum to the final grade for the activity. This page will attempt to enforce a correct total for the final grade and advise you if marks need to be added or removed.</p>
<p>The final grade and the reward for each of the two reviews are set within the activity settings. If you would like to change these settings click the \'Update this Peer Review activity\' button above then come back and set the criteria marks.</p>
';
$string['rewardforreviews'] = 'Two reviews worth {$a} marks each (from activity settings)';
$string['totalmarksabove'] = 'Sum of marks above';
$string['totalmarksforgrade'] = 'Marks set as Grade';
$string['difference'] = 'Difference';
$string['saveanddisplay'] = 'Save and display';
$string['criteriachangewarning'] = 'Warning: Reviews have already been completed. Changing criteria now could be dangerous.'; // Plural.
$string['mustentercriteria'] = 'You must now create criteria. Ensure criteria values add up to the activity grade value.';
$string['marksdontaddup'] = 'Criteria + Reward &ne; Grade<br />';
$string['criteriaupdated'] = 'Criteria updated';

// View page strings.
$string['setcriteria'] = 'Set Criteria';
$string['criteriaaftersubmission'] = 'Criteria used for reviewing and afterwards';
$string['criteriabeforesubmission'] = 'Criteria shown to students before submission';
$string['nocriteriaset'] = 'No criteria have been set.';
$string['notopen'] = 'This activity is not open for submissions.';
$string['submission'] = 'Submission';
$string['submit'] = 'Submit';
$string['reviews'] = 'Reviews';
$string['feedback'] = 'Feedback';
$string['submitbelow'] = 'Submit below';
$string['closed'] = 'Activity closed. Cannot submit';
$string['submitfirst'] = 'Submit first';
$string['notavailable'] = 'Not available yet';
$string['submitted'] = 'Submitted';
$string['reviewsonemore'] = 'Complete one more';
$string['reviewsnotallocated'] = 'Not allocated, return later';
$string['completereviewsbelow'] = 'Complete reviews below';
$string['notavailable'] = 'Not available yet';
$string['reviewscomplete'] = 'Reviews complete';
$string['markassigned'] = 'Mark assigned';
$string['marknotassigned'] = 'Waiting for mark';
$string['reviewnumber'] = 'Review {$a} of 2';
$string['showdescription'] = 'Show peer review description';
$string['hidedescription'] = 'Hide description';
$string['commentinstructions'] = '<em>Offer praise and positive suggestions for improvements. Remember, this student is learning just like you.</em>';
$string['criteriainstructions'] = '<em>Click in each box only if the criterion has been <strong>fully met</strong>.</em>';
$string['savereview'] = 'Save Review...';
$string['description'] = 'Peer Review Description';
$string['nocommentalert'] = 'You must enter a comment.';
$string['savingreview'] = 'Saving Review...';
$string['reviewalreadysaved'] = 'Peer review already saved.';
$string['peerreviewreceivedmessage'] = 'One of your peers has completed a review of your submission.';
$string['peerreviewreceivedsubject'] = 'A peer has reviewed your submission';
$string['peerreviewreceivedlinktext'] = 'Click here to see reviews you have received';
$string['reviewsaved'] = 'Review saved';
$string['yoursubmission'] = 'Your Submission';
$string['yourreviewing'] = 'Your Reviewing';
$string['grade'] = 'Grade';
$string['waitingforpeers'] = 'Waiting for peer reviews';
$string['waitingforpeerstosubmit'] = 'Waiting for peers to submit';
$string['waitingforteacher'] = 'Waiting for teacher moderation';
$string['reviewconcensus'] = 'Peer reviews in consensus';
$string['reviewsoverridden'] = 'Peer reviews overridden by teacher';
$string['showsubmission'] = 'Show submission';
$string['clicktodownload'] = 'Click to download file to review';
$string['clicktoview'] = 'Click to view submission to review';
$string['submittedtime'] = 'Time';
$string['completedlabel'] = 'reviews worth {$a} marks each';
$string['completed'] = 'Completed';
$string['tooshort'] = 'Too short';
$string['reviewtimetaken'] = 'Time taken';
$string['notenoughmoderationstocompare'] = 'No comparable teacher reviews yet';
$string['notenoughreviewstocompare'] = 'Not enough reviews to compare yet';
$string['reviewcomments'] = 'Comments';
$string['reviewaccuracy'] = 'Accuracy';
$string['flagged'] = 'Flagged';
$string['flaglink1'] = 'Un-flag this review';
$string['flaglink2'] = 'Flag this review';
$string['flagprompt1'] = 'This review has been flagged for teacher attention.';
$string['flagprompt2'] = 'Unhappy with this review?';
$string['flags'] = 'Flags';
$string['flags0'] = 'None of your reviews were flagged';
$string['flags1'] = 'One of your reviews was flagged';
$string['flags2'] = 'Both of your reviews were flagged';
$string['reviewsofyoursubmission'] = 'Reviews of Your Submission';
$string['conductedby'] = 'Conducted by';
$string['noreviews'] = 'No reviews have been made of your submission yet.';

// Upload form.
$string['submituploadedfile'] = 'Submit Uploaded File...';
$string['singleuploadwarning'] = 'Warning: You can only submit once.';
$string['singleuploadquestion'] = 'Are you sure you want to submit? <BR> You can only submit once';
$string['onlinesubmission'] = 'Submission';
$string['nosubmission'] = 'Nothing was submitted. You must submit something.';
$string['incorrectfileextension'] = 'The file you uploaded was not the type of file required. You need to submit a file with a .{$a} file extension.';
$string['updatecancelled'] = 'Update Cancelled';
$string['poolnotlargeenough'] = 'Congratulations, you are one of the first to submit!<br /><br />You will receive an email when reviews are allocated to you.';
$string['uploadsuccessful'] = 'Submission successful';
$string['resubmit'] = 'You have already submitted. You cannot resubmit. If you believe you have submitted the wrong file, contact your teacher.';
$string['reviewsallocatedsubject'] = 'You can now begin reviews.';
$string['reviewsallocated'] = 'Reviews have been allocated. You may now continue to complete reviews.';
$string['reviewsallocatedlinktext'] = 'Click here to begin reviews';

// Flagging reviews.
$string['reviewunflagged'] = 'Review un-flagged';
$string['reviewflagged'] = 'Review flagged';

// Reviewingbenefitsofpeerreview.
$string['getthedocument'] = 'Step 1. <em>Save</em> the document then open it';
$string['reviewdocument'] = 'Step 2. Review the submission';

// Submissions page.
$string['reviewallocation'] = 'How are reviews allocated?';
$string['reviewallocation_help'] = '
<br><br><br>
<p>Reviews are allocated automatically by the system using a backwards-allocation method.</p>
<p>Initial submissions are pooled and early submitters are told they will be notified by email when they can commence reviewing.</p>
<p>When the initial pool reaches the required size (around 4 to 5), each early submitter is allocated two reviews from the initial pool.</p>
<p>Later submitters review the submissions of two students who submitted before them (but not immediately before them). In this way, most students can review immediately after they submit.</p>
<p>The chain of review relationships can continue indefinitely after this point in time.</p>
<p>Here are some facts about the number of reviews students will conduct and receive.</p>
</br>
<td>
<ul>
    <li>All students are expected to complete two reviews (usually with the incentive of marks).</li>
    <li>Most students receive two peer reviews.</li>
    <li>Students in the initial pool may receive more than two peer reviews.</li>
    <li>Students at the tail-end of the reviewing chain will receive one or zero reviews, and these must be supplemented by the teacher.</li>
</ul>
';
$string['submission_help'] = '
<p>The Submission column shows the following.</p>
<ul>
    <li><strong>File Icon</strong><br />
    Click on the file icon to download or view the student\'s submission. The icon will differ according to the file type used.</li>
    <br />
    <li><strong>Submission Date and Time</strong><br />
    When the submission was made. Reviews made by the student do not affect this submission date/time.</li>
    <br />
    <li><strong>Resubmit Link</strong><br />
    Students can only submit once. If they submit the wrong file, a teacher can submit the correct file for them. Resubmitting removes the original submission and replaces it with a new file. Resubmitting does not change any other details about the student\'s submission, or their reviews, or reviews they have received. If resubmission is done after reviews have been made, the teacher may have to complete a moderation review as peer reviews of this student will be based on their original submission. Be wary when resubmitting  a student\'s submission after they have completed reviews. If they have, they will have seen other students\' submissions. Make a policy to accept resubmissions only if the student has not completed reviews, unless you implicitly trust the student.</li>
</ul>
';
$string['reviewsbystudent'] = 'Reviews by<br /> student';
$string['reviewsbystudent_help'] = '
<p>These are the reviews conducted <em>by</em> the student whose details are shown in the same row.</p>
<p><strong>Suspicious Reviews</strong></p>
<p>Students may attempt to submit fake reviews without actually reviewing their peers submission. Such reviews are often characterised by:</p>
<ul>
    <li>taking a short period of time to complete, and</li>
    <li>providing only a minimal comment.</li>
</ul>
<p>Suspicious reviews are marked with a question mark (?) on the button that leads to the review, in the row of the student who made the suspicious review.</p>
<p><strong>Flagged Reviews</strong></p>
<p>If a student is unhappy with a review they have received, they may flag the review in order to attract the attention of the teacher.</p>
<p>Flagged reviews are marked by an F on review buttons in this column.</p>
';
$string['moderationstitle'] = 'Moderation<br /> Count';
$string['moderationtarget'] = 'Moderation Target';
$string['moderationtarget_help'] = '
<p>A moderation is a review conducted by a teacher. The number of moderations is counted across all activities in a course.</p>
<p>It is possible to rely on peer consensus to determine a grade for a student in a Peer Review activity.</p>
<p>If a series of Peer Review activities are used in a course, and the Teacher only moderates to resolve conflicts, high performing students, who always produce good submissions and are always reviewed well by peers, may not hear from the Teacher and may feel cheated because of that.</p>
<p>A Moderation Target allows a Teacher to set targets for themselves at different points during the course. For example, if there are six Peer review activities, it may be prudent to set a target of 1 moderation at the second activity, 2 at the fourth and 3 at the sixth. Students then get a sense that the Teacher is overseeing the review process. Top students are usually easy to moderate, so this creates little additional work.</p>
<p>Note: A Moderation Target is a personal setting of each Teacher, so if multiple markers are working on the same activity, they may need to agree to set a particular target before moderating.</p>
';
$string['moderationtargetnotmet'] = 'Moderation target not met';
$string['moderationtargetwhy'] = 'Why set a Moderation Target?';
$string['status'] = 'Status';
$string['status_help'] = '
<p>The status of a submission indicates whether a submission moderation by a teacher.</p>
<strong>Moderation is required</strong><br />
<table>
<tr>
<td><span class="errorStatus">&lt;2 Reviews</span></td>
<td>Fewer than two reviews have been conducted on this students submission. If it is unlikely that more reviews will be made in future, the teacher may need to create a moderation review.</td>
</tr>
<tr>
<td><span class="errorStatus">Conflicting</span></td>
<td>Two or more reviews have been conducted, but they do not agree on one or more criteria.</td>
</tr>
<tr>
<td><span class="errorStatus">Flagged</span></td>
<td>The student who submitted the submission is unhappy about one of the reviews they have recieved and believes teacher attention is needed.</td>
</tr>
<tr>
<td><span class="errorStatus">Conflicting, Flagged</span></td>
<td>A combination of the above two.</td>
</tr>
</table>
<strong>Moderation is not required</strong><br />
<table>
<tr>
<td><span class="goodStatus">Consensus</span></td>
<td>Two or more reviews have been conducted and they are in agreement.</td>
</tr>
<tr>
<td><span class="goodStatus">Overridden</span></td>
<td>A teacher has created a moderation review (which overrides other reviews).</td>
</tr>
</table>
';
$string['seedoreviews'] = 'See/Do reviews<br /> of submission';
$string['seedoreviews_help'] = '
<p>Peer reviews of the submission can be viewed and edited.</p>
<p>The teacher can also create moderation reviews by clicking the button in this column.</p>
';
$string['suggestedgrade'] = 'Suggested<br /> grade';
$string['suggestedgrade_help'] = '
<p>If submission reviews are in consensus, or have been overridden, a mark will be suggested by the system.</p>
<p>Suggested marks will not be applied until either the "Set" button or "Set all unset calculable grades" button is pressed.</p>
';
$string['finalgrade'] = 'Final<br /> grade';
$string['finalgrade_help'] = '
<p>If set, this is the grade appearing in the Grades area.</p>
';
$string['strftimeintable'] = '%a, %d %b, %I:%M %p';
$string['resubmitlabel'] = 'Resubmit';
$string['concensus'] = 'Consensus';
$string['conflicting'] = 'Conflicting';
$string['flagged'] = 'Flagged';
$string['review'] = 'Review';
$string['overridden'] = 'Overridden';
$string['set'] = 'Set';
$string['gradenotanumber'] = 'The grade value is not a number';
$string['notset'] = 'Not set';
$string['lessthantworeviews'] = '&lt;2 Reviews';
$string['massmark'] = 'Set all unset calculable grades';
$string['massmark_help'] = '
<p>Clicking this button will cause all submissions with a suggested grade, but no final grade, to have their suggested grade applied as a final grade.
In other words, for all submissions, if a suggested grade is available, and a grade has not be applied as a final grade, the suggested grade will be set as the final grade.</p>
<p>With this button, moderation and release-of-grades can be achieved in two phases. Moderations can be applied to resolve any conflicts between reviews. Once all conflicts are resolved, this button can be used to release all grades.</p>
<p>If late submissions arrive, this button can be used to set grades for late arriving submissions, without affecting earlier submissions with final grades already set.</p>
';
$string['pagesize'] = 'Page size';
$string['pagesize_help'] = 'The number of submissions shown on each page.';
$string['numberofstudentswarning'] = 'Warning: To use a Peer Review activity, there must be at least 5 students (and preferably more).';
$string['quickgrade'] = 'Quick grading';
$string['quickgrade_help'] = 'Quick grading allows you to manually set marks for submissions. Normally marks should be suggested on the basis of reviews, so you should not need to set marks manually, except in special circumstances.';

// Marking.
$string['early'] = '{$a} early';
$string['late'] = '{$a} late';
$string['moderations'] = 'Moderation Count';
$string['moderationrequired'] = 'Moderation required';
$string['newreview'] = 'New teacher review';
$string['whatdostudentssee'] = 'What do students see?';
$string['whatdostudentssee_help'] = '
<p>Students see a single, unfilled column of check boxes, the criteria and a single text area for entering comments.</p>
<p>Perhaps a better question is: "what don\'t students see?"</p>
<ul>
    <li><strong>Students don\'t see other students\' names or submission details</strong><br />
    In the student view, all student details are anonymised.</li>
    <br />
    <li><strong>Students don\'t see other student reviews</strong><br />
    Students do not see the peer reviews of the current submission that were conducted by other students before them. These are show to teachers as a useful guide when reviewing, and also as a way of keeping an eye on students\' review correctness.</li>
    <br />
    <li><strong>Students don\'t have a list of pre-saved comments</strong><br />
    Students do not have the luxury of using pre-saved comments. Writing original comments is part of their evaluation task. For teachers, the pre-saved comments are useful for quick and consistent moderation; they are particularly useful when multiple teachers are marking.</li>
</ul>
';
$string['comment'] = 'Comment';
$string['savecomments'] = 'Save changes';
$string['savedcomments'] = 'Pre-saved Comments';
$string['savedcomments_help'] = '
<p>Pre-saved comments are available to markers during marking. Students do not see pre-saved comments.</p>
<p>All markers share a single set of pre-saved comments for each assignment.</p>
<p>The purpose of pre-saved comments is to speed up the marking process and to provide consistency across markers.</p>
<p>Pre-saved comments are saved when a pre-existing review or a new moderation review is saved.</p>
';
$string['savenew'] = 'Save new review only';
$string['savepreexistingonly'] = 'Save changes to pre-existing reviews only';
$string['savenewandold'] = 'Save new review and changes to pre-existing reviews';
$string['moderationbuttons'] = 'Buttons shown during moderations';
$string['moderationbuttons_help'] = '
<p>Buttons are shown at the bottom of the moderation (feedback) window.</p>
<p>Depending on if there are previous reviews, and depending on where you are in the order of the list of submissions, you will see some or all of the following buttons.</p>
<ul>
 <li><strong>Cancel</strong><br />
    Closes the current window without saving any changes.</li>   <br />
    <li><strong>Save changes to pre-existing reviews only</strong><br />
    A teacher has the potential to alter reviews made previously by students or teachers. This is usually done on the behalf of students who cannot change reviews they have made after reviews are saved.</li>
    <br />
    <li><strong>Save new review only</strong><br />
    Saves new review information and closes the current window. This option does not save changes to pre-existing reviews.</li>
    <br />
    <li><strong>Save new review and changes to pre-existing reviews</strong><br />
   Save new review made by the teacher and save the changes to-pre-existing reviews made previously by students</li>
</ul>
';
$string['quick'] = 'Quick';
$string['characters'] = 'characters';


$string['nosubmitusers'] = 'No users submit';
$string['good'] = 'Good';
$string['short'] = 'Short';
$string['resubmissionwarning'] = 'Resubmission Warning';
$string['uploada'] = 'Upload a';
$string['witha'] = 'With a';
$string['file'] = 'File';
$string['extension'] = 'Extension';
$string['submissions'] = 'Submissions';
$string['nocompletesubmissions'] = 'No Complete Submissions';
$string['goodlength'] = 'Good length';
$string['poor'] = 'Poor';
$string['emailstatus'] = 'Email status';
$string['previouslyset'] = 'Previously set';
$string['nochange'] = 'No change';
$string['emailnotsent'] = 'Email not sent';
$string['emailsent'] = 'Email sent';
$string['unabletoset'] = 'Unable to set';
$string['gradeset'] = 'Grade set';
$string['hidesubmission'] = 'Hide submission';
$string['showsubmissiontoreview'] = 'Show submission to continue';
$string['getthedocumenttoreview'] = 'Step 1. <em>Save</em> the document to continue';
$string['gradesetmessage'] = 'You have been assigned a grade';
$string['gradeassignedlinktext'] = 'Click here to see the grade you have been assigned';
$string['timeremaining'] = 'Remaining';
$string['uploadafile'] = 'Upload a {$a} file';
$string['analysis'] = 'Analysis';
$string['benefitsofpeerreview'] = 'Benefits of Peer Review Assignments';
$string['benefitsofpeerreview_help'] = '<BR><BR><BR><BR><BR><BR><BR><BR><BR><BR><BR><BR><BR>
<BR><BR><BR><BR><BR><BR><BR><BR><BR><BR>
<h5>Benefits of Peer Review Assignments</h5>
<p>Peer Review assignments can deliver benefits to both teachers and students.</p>
<ul>
    <li><strong>Higher Order Thinking Skills</strong><br />
    In order to evaluate the work of others, students must think about relevant concepts at a higher cognitive level. Peer review also encourages reflection, which is a higher order thinking skill.</li>
    <br />
    <li><strong>Student Involvement in the Assessment Process</strong><br />
    Students are not able to leave the task and forget about it after they submit. Students may learn more deeply when they are involved in both the creation and the assessment of assignment submissions.</li>
    <br />
    <li><strong>Students Receive More Feedback and Faster</strong><br />
    Students receive feedback from peers and instructors. Peer reviews can come much faster than teacher reviews.</li>
    <br />
    <li><strong>Reviewing Encourages a Community of Learners</strong><br />
    Students who view the work of their peers are given a sense that they are not alone in their learning journey.</li>
    <br />
    <li><strong>Streamlined Marking for Teachers</strong><br />
    Teachers can adopt various levels of moderation depending on how much input they wish to have over the assessment process. If peer reviews are correct and consistent the system will suggest a mark, which the teacher can apply as a final mark. Teachers can have greater oversight of submissions, even marking all submissions if they so desire. Armed with pre-existing reviews, a saved list of comments, and a streamlined process, moderating a peer-review assignment be achieved will less effort than regular marking.</li>
</ul>
<p>These benefits depend on the task and criteria being well designed. The advice given on the Analysis page may assist you in improving your Peer Review assignments.</p>
';
$string['statistic'] = 'Statistic';
$string['analysislabelsubmissions'] = 'Submissions';
$string['analysislabelnumberofstudents'] = 'Number of students';
$string['analysislabelnumberofsubmissions'] = 'Number of submissions';
$string['analysislabelsubmissionrate'] = 'Submission rate';
$string['analysislabelaveragewait'] = 'Average time from submission to receipt of first review';
$string['analysislabelmedianwait'] = 'Median time from submission to receipt of first review';
$string['advice'] = 'Advice';
$string['analysislabelreviewing'] = 'Reviewing';
$string['analysislabelreviewscompleted'] = 'Reviews completed';
$string['advicereviewmin'] = 'Number of reviews must reach {$a} before statistics are calculated';
$string['advicereviewrate'] = 'Consider offering more reward marks for reviewing';
$string['analysislabelreviewrate'] = 'Review rate';
$string['analysislabelnormalisedaveragereviewtime'] = 'Normalised average review time (ignores outliers)';
$string['advicereviewtime'] = 'Consider making reviewing more involved';
$string['analysislabelaveragereviewtime'] = 'Average review time';
$string['analysislabelstddevreviewtime'] = 'Standard deviation of review times';
$string['analysislabelminreview'] = 'Quickest review';
$string['analysislabelmaxreview'] = 'Longest review';
$string['analysislabelnormalisedaveragecomment'] = 'Normalised average comment length (ignores outliers)';
$string['advicecommentlength'] = 'Consider providing suggestions for comments';
$string['analysislabelaveragecomment'] = 'Average comment length';
$string['analysislabelstddevcomment'] = 'Standard deviation of comment lengths';
$string['analysislabelmincomment'] = 'Shortest comment';
$string['analysislabelmaxcomment'] = 'Longest comment';
$string['analysislabelflags'] = 'Number of flags';
$string['analysislabelflagrate'] = 'Flag rate';
$string['adviceflagrate'] = 'Consider making criteria more objective';
$string['analysislabelmoderations'] = 'Moderation reviews by teachers';
$string['analysislabelmoderationrate'] = 'Moderation rate';
$string['advicemoderationrate'] = 'Consider reducing the number of criteria and providing more detail in criteria';
$string['analysislabelfeedbackattention'] = 'Feedback Attention';
$string['numberofreviewsviewed'] = 'Number of reviews viewed by reviewee';
$string['reviewattentionrate'] = 'Review attention rate';
$string['advicereviewattentionrate'] = 'Consider directing students to view their review feedback';
$string['numberofreviewviews'] = 'Number of views by all reviewees';
$string['averageviewrate'] = 'Average number of views per review';
$string['averageperiodbewtweenreviewandview'] = 'Average period between review completion and view by reviewee';
$string['medianperiodbewtweenreviewandview'] = 'Median period between review completion and view by reviewee';
$string['analysislabelcheckedcount'] = 'Checked Count';
$string['analysislabelcheckedrate'] = 'Checked Rate';
$string['analysislabelcriteria'] = 'Criteria';
$string['advicecheckedrate'] = 'Consider reducing task difficulty or lowering criterion standard';


$string['completionsubmit'] = 'Student must submit to and complete reviews for this activity to complete it';
$string['mustcheck'] = 'Check review';
$string['mustreview'] = 'Review other submissions';
$string['mustsubmit'] = 'Add submission';

$string['warningalreadysubmitted'] = 'Warning: Submissions have been made. Changing these settings now could be dangerous';
$string['longerthanmost'] = 'Longer than most';
$string['shorterthanmost'] = 'Shorter than most';
