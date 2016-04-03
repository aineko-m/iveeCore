<?php
/**
 * ProtoResponse class file.
 *
 * PHP version 5.4
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/ProtoResponse.php
 */

namespace iveeCrest\Responses;

use iveeCore\Config;

/**
 * ProtoResponse is used for temporarily holding CREST response data and deciding and instantiating the actual response
 * objects, acting as a sort of factory.
 *
 * @category IveeCrest
 * @package  IveeCrestResponses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCrest/Responses/ProtoResponse.php
 */
class ProtoResponse
{
    /**
     * @var string $key under which this response is cached
     */
    protected $key;

    /**
     * @var array $header of http response
     */
    protected $header = [];

    /**
     * Constructor.
     *
     * @param string $key under which the response will be cached
     */
    public function __construct($key)
    {
        $this->key = $key;
    }

    /**
     * Callback function specified with CURLOPT_HEADERFUNCTION, used to process header lines.
     *
     * @param curl_handle $curl passed to callback
     * @param string $headerLine a single line from the http response header
     *
     * @return int the length of the input line (this is required)
     */
    public function handleCurlHeaderLine($curl, $headerLine)
    {
        $frags = explode(": ", $headerLine);
        if (count($frags) == 2) {
            $this->header[$frags[0]] = trim($frags[1]);
        }
        return strlen($headerLine);
    }

    /**
     * Creates an response object.
     *
     * @param string $content JSON encoded response content
     * @param array $info the curl info array
     *
     * @return \iveeCrest\Responses\BaseResponse
     */
    public function makeResponseObj($content, array $info)
    {
        $decodedContent = json_decode($content);

        //for some reason curl sometimes fails to decompress gzipped responses, so we must do it manually
        if (empty($decodedContent)) {
            if (strlen($content) > 0
                and isset($this->header['Content-Encoding'])
                and $this->header['Content-Encoding'] == 'gzip'
            ) {
                $decodedContent = json_decode(gzdecode($content));
            } else {
                //Sometimes the response is just (correctly) empty. To avoid special casing, create emtpy stdClass obj.
                $decodedContent = new \stdClass;
            }
        }

        //delete redundant attributes from the data object
        static::deleteRedundantJsonStrings($decodedContent);

        //here the response object subtype is decided based on the content type returned by CREST
        switch ($this->getContentType()) {
            case 'application/vnd.ccp.eve.AllianceCollection-v2+json':
                $responseClass = Config::getIveeClassName("AllianceCollection");
                break;
            case 'application/vnd.ccp.eve.Alliance-v1+json':
                $responseClass = Config::getIveeClassName("Alliance");
                break;
            case 'application/vnd.ccp.eve.Api-v3+json':
                $responseClass = Config::getIveeClassName("Root");
                break;
            case 'application/vnd.ccp.eve.CharacterLocation-v1+json':
                $responseClass = Config::getIveeClassName("CharacterLocation");
                break;
            case 'application/vnd.ccp.eve.Character-v3+json':
                $responseClass = Config::getIveeClassName("Character");
                break;
            case 'application/vnd.ccp.eve.ConstellationCollection-v1+json':
                $responseClass = Config::getIveeClassName("ConstellationCollection");
                break;
            case 'application/vnd.ccp.eve.Constellation-v1+json':
                $responseClass = Config::getIveeClassName("Constellation");
                break;
            case 'application/vnd.ccp.eve.ContactCollection-v2+json':
                $responseClass = Config::getIveeClassName("ContactCollection");
                break;
            case 'application/vnd.ccp.eve.DogmaAttributeCollection-v1+json':
                $responseClass = Config::getIveeClassName("DogmaAttributeCollection");
                break;
            case 'application/vnd.ccp.eve.DogmaAttribute-v1+json':
                $responseClass = Config::getIveeClassName("DogmaAttribute");
                break;
            case 'application/vnd.ccp.eve.DogmaEffectCollection-v1+json':
                $responseClass = Config::getIveeClassName("DogmaEffectCollection");
                break;
            case 'application/vnd.ccp.eve.DogmaEffect-v1+json':
                $responseClass = Config::getIveeClassName("DogmaEffect");
                break;
            case 'application/vnd.ccp.eve.FittingCollection-v1+json':
                $responseClass = Config::getIveeClassName("FittingCollection");
                break;
            case 'application/vnd.ccp.eve.IncursionCollection-v1+json':
                $responseClass = Config::getIveeClassName("IncursionCollection");
                break;
            case 'application/vnd.ccp.eve.IndustrySystemCollection-v1+json':
                $responseClass = Config::getIveeClassName("IndustrySystemCollection");
                break;
            case 'application/vnd.ccp.eve.IndustryFacilityCollection-v1+json':
                $responseClass = Config::getIveeClassName("IndustryFacilityCollection");
                break;
            case 'application/vnd.ccp.eve.ItemCategoryCollection-v1+json':
                $responseClass = Config::getIveeClassName("ItemCategoryCollection");
                break;
            case 'application/vnd.ccp.eve.ItemCategory-v1+json':
                $responseClass = Config::getIveeClassName("ItemCategory");
                break;
            case 'application/vnd.ccp.eve.ItemGroupCollection-v1+json':
                $responseClass = Config::getIveeClassName("ItemGroupCollection");
                break;
            case 'application/vnd.ccp.eve.ItemGroup-v1+json':
                $responseClass = Config::getIveeClassName("ItemGroup");
                break;
            case 'application/vnd.ccp.eve.ItemTypeCollection-v1+json':
                $responseClass = Config::getIveeClassName("ItemTypeCollection");
                break;
            case 'application/vnd.ccp.eve.ItemType-v2+json':
            case 'application/vnd.ccp.eve.ItemType-v3+json':
                $responseClass = Config::getIveeClassName("ItemType");
                break;
            case 'application/vnd.ccp.eve.Killmail-v1+json':
                $responseClass = Config::getIveeClassName("Killmail");
                break;
            case 'application/vnd.ccp.eve.MarketGroupCollection-v1+json':
                $responseClass = Config::getIveeClassName("MarketGroupCollection");
                break;
            case 'application/vnd.ccp.eve.MarketGroup-v1+json':
                $responseClass = Config::getIveeClassName("MarketGroup");
                break;
            case 'application/vnd.ccp.eve.MarketOrderCollection-v1+json':
                $responseClass = Config::getIveeClassName("MarketOrderCollection");
                break;
            case 'application/vnd.ccp.eve.MarketTypeCollection-v1+json':
                $responseClass = Config::getIveeClassName("MarketTypeCollection");
                break;
            case 'application/vnd.ccp.eve.MarketTypeHistoryCollection-v1+json':
                $responseClass = Config::getIveeClassName("MarketTypeHistoryCollection");
                break;
            case 'application/vnd.ccp.eve.MarketTypePriceCollection-v1+json':
                $responseClass = Config::getIveeClassName("MarketTypePriceCollection");
                break;
            case 'application/vnd.ccp.eve.Options-v1+json':
                $responseClass = Config::getIveeClassName("Options");
                break;
            case 'application/vnd.ccp.eve.Planet-v1+json':
                $responseClass = Config::getIveeClassName("Planet");
                break;
            case 'application/vnd.ccp.eve.RegionCollection-v1+json':
                $responseClass = Config::getIveeClassName("RegionCollection");
                break;
            case 'application/vnd.ccp.eve.Region-v1+json':
                $responseClass = Config::getIveeClassName("Region");
                break;
            case 'application/vnd.ccp.eve.SovCampaignsCollection-v1+json':
                $responseClass = Config::getIveeClassName("SovCampaignsCollection");
                break;
            case 'application/vnd.ccp.eve.SovStructureCollection-v1+json':
                $responseClass = Config::getIveeClassName("SovStructureCollection");
                break;
            case 'application/vnd.ccp.eve.SystemCollection-v1+json':
                $responseClass = Config::getIveeClassName("SystemCollection");
                break;
            case 'application/vnd.ccp.eve.System-v1+json':
                $responseClass = Config::getIveeClassName("System");
                break;
            case 'application/vnd.ccp.eve.TokenDecode-v1+json':
                $responseClass = Config::getIveeClassName("TokenDecode");
                break;
            case 'application/vnd.ccp.eve.TournamentCollection-v1+json':
                $responseClass = Config::getIveeClassName("TournamentCollection");
                break;
            case 'application/vnd.ccp.eve.Tournament-v1+json':
                $responseClass = Config::getIveeClassName("Tournament");
                break;
            case 'application/vnd.ccp.eve.TournamentMatchCollection-v1+json':
                $responseClass = Config::getIveeClassName("TournamentMatchCollection");
                break;
            case 'application/vnd.ccp.eve.TournamentMatch-v1+json':
                $responseClass = Config::getIveeClassName("TournamentMatch");
                break;
            case 'application/vnd.ccp.eve.TournamentPilotStatsCollection-v1+json':
                $responseClass = Config::getIveeClassName("TournamentPilotStatsCollection");
                break;
            case 'application/vnd.ccp.eve.TournamentPilotTournamentStats-v1+json':
                $responseClass = Config::getIveeClassName("TournamentPilotTournamentStats");
                break;
            case 'application/vnd.ccp.eve.TournamentRealtimeMatchFrame-v2+json':
                $responseClass = Config::getIveeClassName("TournamentRealtimeMatchFrame");
                break;
            case 'application/vnd.ccp.eve.TournamentSeriesCollection-v1+json':
                $responseClass = Config::getIveeClassName("TournamentSeriesCollection");
                break;
            case 'application/vnd.ccp.eve.TournamentSeries-v1+json':
                $responseClass = Config::getIveeClassName("TournamentSeries");
                break;
            case 'application/vnd.ccp.eve.TournamentStaticSceneData-v1+json':
                $responseClass = Config::getIveeClassName("TournamentStaticSceneData");
                break;
            case 'application/vnd.ccp.eve.TournamentTeamMember-v1+json':
                $responseClass = Config::getIveeClassName("TournamentTeamMember");
                break;
            case 'application/vnd.ccp.eve.TournamentTeamMemberCollection-v1+json':
                $responseClass = Config::getIveeClassName("TournamentTeamMemberCollection");
                break;
            case 'application/vnd.ccp.eve.TournamentTeam-v1+json':
                $responseClass = Config::getIveeClassName("TournamentTeam");
                break;
            case 'application/vnd.ccp.eve.TournamentTypeBanCollection-v1+json':
                $responseClass = Config::getIveeClassName("TournamentTypeBanCollection");
                break;
            case 'application/vnd.ccp.eve.WarsCollection-v1+json':
                $responseClass = Config::getIveeClassName("WarsCollection");
                break;
            case 'application/vnd.ccp.eve.War-v1+json':
                $responseClass = Config::getIveeClassName("War");
                break;
            case 'application/vnd.ccp.eve.WarKillmails-v1+json':
                $responseClass = Config::getIveeClassName("WarKillmails");
                break;
            default:
                $responseClass = Config::getIveeClassName("BaseResponse");
        }

        return new $responseClass($this->key, $decodedContent, $this->header, $info);
    }

    /**
     * Returns the content type of the response.
     *
     * @return string
     */
    protected function getContentType()
    {
        if (!isset($this->header['Content-Type'])) {
            return '';
        }
        return trim(explode(';', $this->header['Content-Type'])[0]);
    }

    /**
     * Deletes all attributes ending in '_str' from a stdClass object and its children.
     *
     * @param \stdClass $data to be cleaned
     *
     * @return void
     */
    protected static function deleteRedundantJsonStrings(\stdClass $data)
    {
        foreach ($data as $attribute => &$value) {
            if (substr($attribute, -4) == '_str') {
                    unset($data->$attribute);
            } elseif ($value instanceof \stdClass) {
                static::deleteRedundantJsonStrings($value);
            } elseif (is_array($value)) {
                foreach ($value as &$item) {
                    static::deleteRedundantJsonStrings($item);
                }
            }
        }
    }
}
