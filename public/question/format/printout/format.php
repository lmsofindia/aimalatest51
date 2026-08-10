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
 * printout question exporter.
 *
 * @package    qformat_printout
 * @copyright  2018 Stefan Weber (stewe1@gmx.de)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */




defined('MOODLE_INTERNAL') || die();


/**
 * printout question exporter.
 *
 * Exports questions in a printer and human-friendly format.
 *
 * @copyright  2018 Stefan Weber (webers@technikum-wien.at)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qformat_printout extends qformat_default {

    /**
     * @inheritdoc
     */
    public function provide_export() {
        return true;
    }

    /**
     * Write title of question.
     * @param string $questiontext
     *
     * @return string
     */
    protected function writetitle($questiontext) {
        return "<p><li class=\"questiontext\">" . strip_tags($questiontext) . "</li></p>";
    }

    /**
     * Write title of question.
     * @param object $question
     *
     * @return string
     */
    protected function writequestion($question) {

        global $OUTPUT;

        // Category name.
        if ($question->qtype == 'category') {
            $categoryname = str_replace('$course$/top/', "", $question->category);
            return "</ul> <span class=\"category\"> {$categoryname} </span> <ul class='questionlist'>";
        }

        // Print questions depends on question type.
        $expout = "<div>";
        switch($question->qtype) {
            case 'multichoice':
                $expout .= $this->writetitle($question->questiontext);
                $expout .= "<ul class=\"multichoice\">\n";
                foreach ($question->options->answers as $answer) {

                    // Remove <p> tags from answers since the default <p> tags mess up formatting for multiple choice answers.
                    $answertext = strip_tags($answer->answer);
                    $answerpoints = $answer->fraction * 100 . "%";
                    if ($answerpoints > 0) {
                        $class = '"correct points"';
                    } else {
                        $class = '"wrong points"';
                    }
                    $expout .= " <li><span class=$class>($answerpoints)  </span>
                        <span class=\"mcanswer\">{$answertext} </span></li>";
                }
                $expout .= "</ul>\n";
                break;
            case 'calculatedmulti':
            case 'multichoiceset':
            case 'oumultiresponse':
            case 'truefalse':
                $expout .= $this->writetitle($question->questiontext);
                $expout .= "<ul class=\"multichoice\">\n";
                foreach ($question->options->answers as $answer) {
                    if ($answer->fraction > 0) {
                        $class = 'correct';
                    } else {
                        $class = 'wrong';
                    }
                    $answertext = strip_tags($answer->answer);
                    $expout .= " <li class=\"mcanswer $class\">{$answertext}</li>";
                }
                $expout .= "</ul>\n";
                break;
            case 'match':
                $expout .= $this->writetitle($question->questiontext);
                $expout .= "<ul>";
                foreach ($question->options->subquestions as $subquestion) {
                    $questiontext = $subquestion->questiontext;
                    $answertext = $subquestion->answertext;
                    $answertext = strip_tags($answertext);
                    if ($questiontext) {
                        $expout .= "<li>$questiontext-> <span class=\"correct\">$answertext</span></li>";
                    } else {
                        $expout .= "<li><p>XXX</p>-> <span class=\"wrong\">$answertext</span></li>";
                    }
                }
                $expout .= "</ul>";
                break;
            case 'description':
                $expout .= $this->writetitle($question->questiontext);
                break;
            case 'gapfill':
            case 'select':
                $expout .= $this->writetitle($question->questiontext);
                foreach ($question->options->answers as $answer) {
                    $expout .= "{$answer->answer}, ";
                }
                break;
            case 'ddwtos':
            case 'gapselect':
                $i = 1;
                $questiontext = strip_tags($question->questiontext);
                foreach ($question->options->answers as $answer) {
                    $questiontext = str_replace('[[' . $i . ']]',
                        '<span class="correct">[[' . $answer->answer . ']]</span>', $questiontext);
                    $i++;
                }
                $expout .= "<p><li class=\"questiontext\"> {$questiontext}</li></p>";
                foreach ($question->options->answers as $answer) {
                    $expout .= "{$answer->answer}, ";
                }
                break;
            case 'multianswer':
                $expout .= $this->writetitle($question->questiontext);
                $expout .= "<ul>";
                foreach ($question->options->questions as $subquestion) {
                    $expout .= "<li class=\"mcanswer\">{$subquestion->questiontext}</li>";
                }
                $expout .= "</ul>";
                break;
            case 'ddimageortext':
            case 'ddmarker':
                $expout .= $this->writetitle($question->questiontext);
                $expout .= get_string('notsupported', 'qformat_printout');
                break;
            default:
                $expout .= $this->writetitle($question->questiontext);
                $answers = $question->options->answers ?? [];
                if (count($answers) > 1) {
                    $expout .= "<ul>";
                    foreach ($answers as $answer) {
                        $expout .= "<li>". strip_tags($answer->answer) ."</li>";
                    }
                    $expout .= "</ul>";
                } else {
                    foreach ($answers as $answer) {
                        $expout .= "<p>" . get_string('answer') . ": " . strip_tags($answer->answer) . "</p>";
                    }
                }
                $expout .= "<br>";
        }

        // Feedback.
        if ($question->generalfeedback) {
            $expout .= get_string('feedback', 'question') . ": ";
            $expout .= $question->generalfeedback;
        }

        // Question type.
        $expout .= "<p class=\"questiontype\">{$question->name} ";
        $expout .= " (" . get_string('pluginname', "qtype_{$question->qtype}");

        if (!empty($question->options->single)) {
            $expout .= " / " . get_string('answersingleyes', 'qtype_multichoice');
        }
        $expout .= ")</p>";

        // End of question.
        $expout .= "<hr></div>";
        return $expout;
    }


    /**
     * Override method to allow us to add printout headers and footers.
     */
    protected function presave_process($content) {

        global $CFG;

        // Include CSS.
        $csslines = file( "{$CFG->dirroot}/question/format/printout/printout.css" );
        $css = implode( ' ', $csslines );

        $xp =  "<!DOCTYPE html PUBLIC \"-//W3C//DTD printout 1.0 Strict//EN\"\n";
        $xp .= "  \"http://www.w3.org/TR/printout1/DTD/printout1-strict.dtd\">\n";
        $xp .= "<html xmlns=\"http://www.w3.org/1999/printout\">\n";
        $xp .= "<head>\n";
        $xp .= "<meta http-equiv=\"content-type\" content=\"text/html; charset=UTF-8\" />\n";
        $xp .= "<style type=\"text/css\">\n";
        $xp .= $css;
        $xp .= "</style>\n";
        $xp .= "</head>\n";
        $xp .= "<body>\n";
        $xp .= $content;
        $xp .= "</body>\n";
        $xp .= "</html>\n";

        return $xp;
    }

    /**
     * Export as html.
     */
    public function export_file_extension() {
        return '.html';
    }
}
