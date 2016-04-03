<?php
/**
 * Options class file.
 *
 * PHP version 5.4
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/Options.php
 */

namespace iveeCrest\Responses;

/**
 * Options represents responses of HTTP OPTIONS queries to a CREST endpoint.
 * Inheritance: Options -> BaseResponse
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/Options.php
 */
class Options extends BaseResponse
{
    /**
     * Processes and sets the contect during object construction.
     *
     * @param \stdClass $content the content to be set
     *
     * @return void
     */
    protected function setContent(\stdClass $content)
    {
        $representations = [];

        //OPTIONS responses contain doubly JSON encoded data for some reason. Normalize that.
        foreach ($content->representations as $i => $rep) {
            if (isset($rep->acceptType)) {
                $rep->acceptType->jsonDumpOfStructure = json_decode($rep->acceptType->jsonDumpOfStructure);
            } elseif (isset($rep->contentType)) {
                $rep->contentType->jsonDumpOfStructure = json_decode($rep->contentType->jsonDumpOfStructure);
            }

            $representations[$i] = $rep;
        }

        $this->content = $content;
        $this->content->representations = $representations;
    }
}
