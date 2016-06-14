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
     * @var array $representationsToClassNicks maps from CREST representations (Content-Types) to class nicknames.
     */
    protected static $representationsToClassNicks = [
        'application/vnd.ccp.eve.AllianceCollection-v2+json'             => 'AllianceCollection',
        'application/vnd.ccp.eve.Alliance-v1+json'                       => 'Alliance',
        'application/vnd.ccp.eve.Api-v5+json'                            => 'Root',
        'application/vnd.ccp.eve.CharacterLocation-v1+json'              => 'CharacterLocation',
        'application/vnd.ccp.eve.Character-v4+json'                      => 'Character',
        'application/vnd.ccp.eve.CharacterOpportunitiesCollection-v1+json' => 'CharacterOpportunitiesCollection',
        'application/vnd.ccp.eve.ConstellationCollection-v1+json'        => 'ConstellationCollection',
        'application/vnd.ccp.eve.Constellation-v1+json'                  => 'Constellation',
        'application/vnd.ccp.eve.ContactCollection-v2+json'              => 'ContactCollection',
        'application/vnd.ccp.eve.DogmaAttributeCollection-v1+json'       => 'DogmaAttributeCollection',
        'application/vnd.ccp.eve.DogmaAttribute-v1+json'                 => 'DogmaAttribute',
        'application/vnd.ccp.eve.DogmaEffectCollection-v1+json'          => 'DogmaEffectCollection',
        'application/vnd.ccp.eve.DogmaEffect-v1+json'                    => 'DogmaEffect',
        'application/vnd.ccp.eve.FittingCollection-v1+json'              => 'FittingCollection',
        'application/vnd.ccp.eve.IncursionCollection-v1+json'            => 'IncursionCollection',
        'application/vnd.ccp.eve.IndustrySystemCollection-v1+json'       => 'IndustrySystemCollection',
        'application/vnd.ccp.eve.IndustryFacilityCollection-v1+json'     => 'IndustryFacilityCollection',
        'application/vnd.ccp.eve.InsurancePricesCollection-v1+json'      => 'InsurancePricesCollection',
        'application/vnd.ccp.eve.ItemCategoryCollection-v1+json'         => 'ItemCategoryCollection',
        'application/vnd.ccp.eve.ItemCategory-v1+json'                   => 'ItemCategory',
        'application/vnd.ccp.eve.ItemGroupCollection-v1+json'            => 'ItemGroupCollection',
        'application/vnd.ccp.eve.ItemGroup-v1+json'                      => 'ItemGroup',
        'application/vnd.ccp.eve.ItemTypeCollection-v1+json'             => 'ItemTypeCollection',
        'application/vnd.ccp.eve.ItemType-v3+json'                       => 'ItemType',
        'application/vnd.ccp.eve.Killmail-v1+json'                       => 'Killmail',
        'application/vnd.ccp.eve.LoyaltyPointsCollection-v1+json'        => 'LoyaltyPointsCollection',
        'application/vnd.ccp.eve.LoyaltyStoreOffersCollection-v1+json'   => 'LoyaltyStoreOffersCollection',
        'application/vnd.ccp.eve.MarketGroupCollection-v1+json'          => 'MarketGroupCollection',
        'application/vnd.ccp.eve.MarketGroup-v1+json'                    => 'MarketGroup',
        'application/vnd.ccp.eve.MarketOrderCollection-v1+json'          => 'MarketOrderCollection',
        'application/vnd.ccp.eve.MarketOrderCollectionSlim-v1+json'      => 'MarketOrderCollectionSlim',
        'application/vnd.ccp.eve.MarketTypeCollection-v1+json'           => 'MarketTypeCollection',
        'application/vnd.ccp.eve.MarketTypeHistoryCollection-v1+json'    => 'MarketTypeHistoryCollection',
        'application/vnd.ccp.eve.MarketTypePriceCollection-v1+json'      => 'MarketTypePriceCollection',
        'application/vnd.ccp.eve.Moon-v2+json'                           => 'Moon',
        'application/vnd.ccp.eve.NPCCorporationsCollection-v1+json'      => 'NPCCorporationsCollection',
        'application/vnd.ccp.eve.OpportunityGroup-v1+json'               => 'OpportunityGroup',
        'application/vnd.ccp.eve.OpportunityGroupsCollection-v1+json'    => 'OpportunityGroupsCollection',
        'application/vnd.ccp.eve.OpportunityTasksCollection-v1+json'     => 'OpportunityTasksCollection',
        'application/vnd.ccp.eve.Options-v1+json'                        => 'Options',
        'application/vnd.ccp.eve.Planet-v2+json'                         => 'Planet',
        'application/vnd.ccp.eve.RegionCollection-v1+json'               => 'RegionCollection',
        'application/vnd.ccp.eve.Region-v1+json'                         => 'Region',
        'application/vnd.ccp.eve.SovCampaignsCollection-v1+json'         => 'SovCampaignsCollection',
        'application/vnd.ccp.eve.SovStructureCollection-v1+json'         => 'SovStructureCollection',
        'application/vnd.ccp.eve.Stargate-v1+json'                       => 'Stargate',
        'application/vnd.ccp.eve.Station-v1+json'                        => 'StationCrest',
        'application/vnd.ccp.eve.SystemCollection-v1+json'               => 'SystemCollection',
        'application/vnd.ccp.eve.System-v1+json'                         => 'System',
        'application/vnd.ccp.eve.TokenDecode-v1+json'                    => 'TokenDecode',
        'application/vnd.ccp.eve.TournamentCollection-v1+json'           => 'TournamentCollection',
        'application/vnd.ccp.eve.Tournament-v1+json'                     => 'Tournament',
        'application/vnd.ccp.eve.TournamentMatchCollection-v1+json'      => 'TournamentMatchCollection',
        'application/vnd.ccp.eve.TournamentMatch-v1+json'                => 'TournamentMatch',
        'application/vnd.ccp.eve.TournamentPilotStatsCollection-v1+json' => 'TournamentPilotStatsCollection',
        'application/vnd.ccp.eve.TournamentPilotTournamentStats-v1+json' => 'TournamentPilotTournamentStats',
        'application/vnd.ccp.eve.TournamentRealtimeMatchFrame-v2+json'   => 'TournamentRealtimeMatchFrame',
        'application/vnd.ccp.eve.TournamentSeriesCollection-v1+json'     => 'TournamentSeriesCollection',
        'application/vnd.ccp.eve.TournamentSeries-v1+json'               => 'TournamentSeries',
        'application/vnd.ccp.eve.TournamentStaticSceneData-v1+json'      => 'TournamentStaticSceneData',
        'application/vnd.ccp.eve.TournamentTeamMember-v1+json'           => 'TournamentTeamMember',
        'application/vnd.ccp.eve.TournamentTeamMemberCollection-v1+json' => 'TournamentTeamMemberCollection',
        'application/vnd.ccp.eve.TournamentTeam-v1+json'                 => 'TournamentTeam',
        'application/vnd.ccp.eve.TournamentTypeBanCollection-v1+json'    => 'TournamentTypeBanCollection',
        'application/vnd.ccp.eve.WarsCollection-v1+json'                 => 'WarsCollection',
        'application/vnd.ccp.eve.War-v1+json'                            => 'War',
        'application/vnd.ccp.eve.WarKillmails-v1+json'                   => 'WarKillmails'
    ];

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
        //delete redundant attributes from json string, then decode and assign
        $decodedContent = json_decode(static::deleteRedundantJsonAttributes($content));

        //for some reason curl sometimes fails to decompress gzipped responses, so we must do it manually
        if (empty($decodedContent)) {
            if (strlen($content) > 0
                and isset($this->header['Content-Encoding'])
                and $this->header['Content-Encoding'] == 'gzip'
            ) {
                $decodedContent = json_decode(static::deleteRedundantJsonAttributes(gzdecode($content)));
            } else {
                //Sometimes the response is just (correctly) empty. To avoid special casing, create emtpy stdClass obj.
                $decodedContent = new \stdClass;
            }
        }

        //here the response object subtype is decided based on the content type returned by CREST
        $contentType = $this->getContentType();
        if (isset(static::$representationsToClassNicks[$contentType])) {
            $responseClass = Config::getIveeClassName(static::$representationsToClassNicks[$contentType]);
        } else {
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
     * Deletes all attributes ending in '_str' from json encoded string
     *
     * @param string $data to be cleaned
     *
     * @return string
     */
    protected static function deleteRedundantJsonAttributes($data)
    {
        return preg_replace(['("[\w]+_str": "[\d]+", )', '(, "[\w]+_str": "[\d]+"})'], ['', '}'], $data);
    }
}
