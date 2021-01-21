<?php

namespace Escavador\Vespa\Models;

use Escavador\Vespa\Common\Utils;
use Escavador\Vespa\Interfaces\AbstractClient;
use Escavador\Vespa\Interfaces\VespaResult;

class VespaQuery
{
    private $parent_client;

    public function __construct(AbstractClient $client)
    {
        $this->parent_client = $client;
    }

    public final function get(bool $reset = true): VespaResult
    {
        $payload = $this->toArray();
        if ($reset) {
            $this->reset();
        }
        return $this->parent_client->search($payload);
    }

    public final function toArray(): array
    {
        $arr = get_object_vars($this);
        unset($arr['parent_client']);

        return $arr;
    }

    public final function reset(): VespaQuery
    {
        $vars = get_object_vars($this);
        unset($vars['parent_client']);
        $properties = array_keys($vars);

        foreach ($properties as $property) {
            unset($this->$property);
        }

        return $this;
    }

    public final function payload(string $field, $data): VespaQuery
    {
        $this->$field = $data;

        return $this;
    }

    public final function query(string $query): VespaQuery
    {
        $this->query = Utils::removeExtraSpace($query);

        return $this;
    }

    public final function yql(string $yql_statement): VespaQuery
    {
        $this->yql = Utils::removeExtraSpace($yql_statement);

        return $this;
    }

    public final function offset(int $offset): VespaQuery
    {
        $this->offset = $offset;

        return $this;
    }

    public final function hits(int $hits): VespaQuery
    {
        $this->hits = $hits;

        return $this;
    }

    public final function timeout(float $timeout): VespaQuery
    {
        $this->timeout = $timeout;

        return $this;
    }

    public final function type(string $type): VespaQuery
    {
        $this->type = $type;

        return $this;
    }

    public final function traceLevel(int $traceLevel): VespaQuery
    {
        $this->traceLevel = $traceLevel;

        return $this;
    }

    public final function tracelevelRules(int $rules): VespaQuery
    {
        $this->tracelevel = ['rules' => $rules];

        return $this;
    }

    public final function noCache($noCache): VespaQuery
    {
        $this->noCache = $noCache;

        return $this;
    }

    public final function groupingSessionCache(bool $groupingSessionCache): VespaQuery
    {
        $this->groupingSessionCache = $groupingSessionCache;

        return $this;
    }

    public final function searchChain(string $searchChain): VespaQuery
    {
        $this->searchChain = $searchChain;

        return $this;
    }

    public final function user(string $user): VespaQuery
    {
        $this->user = $user;

        return $this;
    }

    public final function recall(array $recall): VespaQuery
    {
        $this->recall = $recall;

        return $this;
    }

    public final function nocachewrite(bool $nocachewrite): VespaQuery
    {
        $this->nocachewrite = $nocachewrite;

        return $this;
    }

    public final function collapsesize(int $collapsesize): VespaQuery
    {
        $this->collapsesize = $collapsesize;

        return $this;
    }

    public final function collapsefield(string $collapsefield): VespaQuery
    {
        $this->collapsefield = $collapsefield;

        return $this;
    }

    public final function queryProfile(string $queryProfile): VespaQuery
    {
        $this->queryProfile = $queryProfile;

        return $this;
    }

    public final function rulesOff(bool $off): VespaQuery
    {
        return $this->rules('off', $off);
    }

    public final function streamingUserid(int $userid): VespaQuery
    {
        return $this->streaming('userid', $userid);
    }

    public final function streamingGroupname(string $groupname): VespaQuery
    {
        return $this->streaming('groupname', $groupname);
    }

    public final function streamingSelection(string $selection): VespaQuery
    {
        return $this->streaming('selection', $selection);
    }

    public final function streamingPriority(string $priority): VespaQuery
    {
        return $this->streaming('priority', $priority);
    }

    public final function streamingMaxbucketspervisitor(int $maxbucketspervisitor): VespaQuery
    {
        return $this->streaming('maxbucketspervisitor', $maxbucketspervisitor);
    }

    public final function rulesRulebase(string $rulebase): VespaQuery
    {
        return $this->rules('rulebase', $rulebase);
    }

    public final function collapseSummary(string $summary): VespaQuery
    {
        return $this->collapse('summary', $summary);
    }

    public final function posLL(string $ll): VespaQuery
    {
        return $this->pos('ll', $ll);
    }

    public final function posRadius(string $radius): VespaQuery
    {
        return $this->pos('radius', $radius);
    }

    public final function posBB(array $bb): VespaQuery
    {
        return $this->pos('bb', $bb);
    }

    public final function posAttribute(string $attribute): VespaQuery
    {
        return $this->pos('attribute', $attribute);
    }

    public final function rankingProfile(string $profile): VespaQuery
    {
        return $this->ranking('profile', $profile);
    }

    public final function rankingSorting(string $sorting): VespaQuery
    {
        return $this->ranking('sorting', $sorting);
    }

    public final function rankingQueryCache(bool $queryCache): VespaQuery
    {
        return $this->ranking('queryCache', $queryCache);
    }

    public final function rankingFreshness(string $freshness): VespaQuery
    {
        return $this->ranking('freshness', $freshness);
    }

    public final function rankingMatchPhaseMaxHits($maxHits): VespaQuery
    {
        return $this->rankingMatchPhase('maxHits', $maxHits);
    }

    public final function rankingMatchPhaseAttribute(string $attribute): VespaQuery
    {
        return $this->rankingMatchPhase('attribute', $attribute);
    }

    public final function rankingMatchPhaseAscending(bool $ascending): VespaQuery
    {
        return $this->rankingMatchPhase('ascending', $ascending);
    }

    public final function rankingMatchPhaseDiversityAttribute(string $attribute): VespaQuery
    {
        return $this->rankingMatchPhaseDiversity('attribute', $attribute);
    }

    public final function rankingMatchPhaseDiversityMinGroups($minGroups): VespaQuery
    {
        return $this->rankingMatchPhaseDiversity('minGroups', $minGroups);
    }

    public final function rankingListFeatures(bool $listFeatures): VespaQuery
    {
        return $this->ranking('listFeatures', $listFeatures);
    }

    public final function rankingLocation(string $location): VespaQuery
    {
        return $this->ranking('location', $location);
    }

    public final function rankingFeaturesFeatureName($featurename): VespaQuery
    {
        return $this->rankingFeatures('featurename', $featurename);
    }

    public final function rankingPropertiesPropertyName($propertyname): VespaQuery
    {
        return $this->rankingProperties('properties', $propertyname);
    }

    public final function modelType(string $type): VespaQuery
    {
        return $this->model('type', $type);
    }

    public final function modelRestrict(array $restrict): VespaQuery
    {
        return $this->model('restrict', implode(", ", $restrict));
    }

    public final function modelSources(array $sources): VespaQuery
    {
        return $this->model('sources', implode(", ", $sources));
    }

    public final function modelLanguage(string $language): VespaQuery
    {
        return $this->model('language', $language);
    }

    public final function modelEncoding(string $encoding): VespaQuery
    {
        return $this->model('encoding', $encoding);
    }

    public final function modelQueryString(string $queryString): VespaQuery
    {
        return $this->model('queryString', $queryString);
    }

    public final function modelSearchPath(string $searchPath): VespaQuery
    {
        return $this->model('searchPath', $searchPath);
    }

    public final function modelDefaultIndex(string $defaultIndex): VespaQuery
    {
        return $this->model('defaultIndex', $defaultIndex);
    }

    public final function presentationBolding(bool $bolding): VespaQuery
    {
        return $this->presentation('bolding', $bolding);
    }

    public final function presentationFormat(string $format): VespaQuery
    {
        return $this->presentation('format', $format);
    }

    public final function presentationSummary(string $summary): VespaQuery
    {
        return $this->presentation('summary', $summary);
    }

    public final function presentationTemplate(string $template): VespaQuery
    {
        return $this->presentation('template', $template);
    }

    public final function presentationTiming(bool $timing): VespaQuery
    {
        return $this->presentation('timing', $timing);
    }

    public final function traceTimestamps(bool $timestamps): VespaQuery
    {
        return $this->presentation('timestamps', $timestamps);
    }

    public final function metricsIgnore(bool $ignore): VespaQuery
    {
        return $this->metrics('ignore', $ignore);
    }

    private function rankingProperties($key, $value)
    {
        $this->ranking('properties',  [$key => $value]);

        return $this;
    }

    private function rankingMatchPhase($key, $value)
    {
        $this->ranking('matchPhase',  [$key => $value]);

        return $this;
    }

    private function rankingMatchPhaseDiversity($key, $value)
    {
        $this->rankingMatchPhase('diversity',  [$key => $value]);

        return $this;
    }

    private function rankingFeatures($key, $value)
    {
        $this->ranking('features',  [$key => $value]);

        return $this;
    }

    private function ranking($key, $value)
    {
        if (!property_exists($this, 'ranking'))
            $this->ranking = [];

        $this->ranking[$key] = $value;
        return $this;
    }

    private function model($key, $value)
    {
        if (!property_exists($this, 'model'))
            $this->model = [];

        $this->model[$key] = $value;
        return $this;
    }

    private function presentation($key, $value)
    {
        if (!property_exists($this, 'presentation'))
            $this->presentation = [];

        $this->presentation[$key] = $value;
        return $this;
    }

    private function trace($key, $value)
    {
        if (!property_exists($this, 'trace'))
            $this->trace = [];

        $this->trace[$key] = $value;
        return $this;
    }

    private function metrics($key, $value)
    {
        if (!property_exists($this, 'metrics'))
            $this->metrics = [];

        $this->metrics[$key] = $value;
        return $this;
    }

    private function collapse($key, $value)
    {
        if (!property_exists($this, 'collapse'))
            $this->collapse = [];

        $this->collapse[$key] = $value;
        return $this;
    }

    private function pos($key, $value)
    {
        if (!property_exists($this, 'pos'))
            $this->pos = [];

        $this->pos[$key] = $value;
        return $this;
    }

    private function rules($key, $value)
    {
        if (!property_exists($this, 'rules'))
            $this->rules = [];

        $this->rules[$key] = $value;
        return $this;
    }

    private function streaming($key, $value)
    {
        if (!property_exists($this, 'streaming'))
            $this->streaming = [];

        $this->streaming[$key] = $value;
        return $this;
    }
}
