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

    final public function get(bool $reset = true): VespaResult
    {
        $payload = $this->toArray();
        if ($reset) {
            $this->reset();
        }
        return $this->parent_client->search($payload);
    }

    final public function toArray(): array
    {
        $arr = get_object_vars($this);
        unset($arr['parent_client']);

        return $arr;
    }

    final public function reset(): VespaQuery
    {
        $vars = get_object_vars($this);
        unset($vars['parent_client']);
        $properties = array_keys($vars);

        foreach ($properties as $property) {
            unset($this->$property);
        }

        return $this;
    }

    final public function payload(string $field, $data): VespaQuery
    {
        $this->$field = $data;

        return $this;
    }

    final public function query(string $query): VespaQuery
    {
        $this->query = Utils::removeExtraSpace($query);

        return $this;
    }

    final public function yql(string $yql_statement): VespaQuery
    {
        $this->yql = Utils::removeExtraSpace($yql_statement);

        return $this;
    }

    final public function offset(int $offset): VespaQuery
    {
        $this->offset = $offset;

        return $this;
    }

    final public function hits(int $hits): VespaQuery
    {
        $this->hits = $hits;

        return $this;
    }

    final public function timeout(float $timeout): VespaQuery
    {
        $this->timeout = $timeout;

        return $this;
    }

    final public function type(string $type): VespaQuery
    {
        $this->type = $type;

        return $this;
    }

    final public function traceLevel(int $traceLevel): VespaQuery
    {
        $this->traceLevel = $traceLevel;

        return $this;
    }

    final public function tracelevelRules(int $rules): VespaQuery
    {
        $this->tracelevel = ['rules' => $rules];

        return $this;
    }

    final public function noCache($noCache): VespaQuery
    {
        $this->noCache = $noCache;

        return $this;
    }

    final public function groupingSessionCache(bool $groupingSessionCache): VespaQuery
    {
        $this->groupingSessionCache = $groupingSessionCache;

        return $this;
    }

    final public function searchChain(string $searchChain): VespaQuery
    {
        $this->searchChain = $searchChain;

        return $this;
    }

    final public function user(string $user): VespaQuery
    {
        $this->user = $user;

        return $this;
    }

    final public function recall(array $recall): VespaQuery
    {
        $this->recall = $recall;

        return $this;
    }

    final public function nocachewrite(bool $nocachewrite): VespaQuery
    {
        $this->nocachewrite = $nocachewrite;

        return $this;
    }

    final public function collapsesize(int $collapsesize): VespaQuery
    {
        $this->collapsesize = $collapsesize;

        return $this;
    }

    final public function collapsefield(string $collapsefield): VespaQuery
    {
        $this->collapsefield = $collapsefield;

        return $this;
    }

    final public function queryProfile(string $queryProfile): VespaQuery
    {
        $this->queryProfile = $queryProfile;

        return $this;
    }

    final public function rulesOff(bool $off): VespaQuery
    {
        return $this->rules('off', $off);
    }

    final public function streamingUserid(int $userid): VespaQuery
    {
        return $this->streaming('userid', $userid);
    }

    final public function streamingGroupname(string $groupname): VespaQuery
    {
        return $this->streaming('groupname', $groupname);
    }

    final public function streamingSelection(string $selection): VespaQuery
    {
        return $this->streaming('selection', $selection);
    }

    final public function streamingPriority(string $priority): VespaQuery
    {
        return $this->streaming('priority', $priority);
    }

    final public function streamingMaxbucketspervisitor(int $maxbucketspervisitor): VespaQuery
    {
        return $this->streaming('maxbucketspervisitor', $maxbucketspervisitor);
    }

    final public function rulesRulebase(string $rulebase): VespaQuery
    {
        return $this->rules('rulebase', $rulebase);
    }

    final public function collapseSummary(string $summary): VespaQuery
    {
        return $this->collapse('summary', $summary);
    }

    final public function posLL(string $ll): VespaQuery
    {
        return $this->pos('ll', $ll);
    }

    final public function posRadius(string $radius): VespaQuery
    {
        return $this->pos('radius', $radius);
    }

    final public function posBB(array $bb): VespaQuery
    {
        return $this->pos('bb', $bb);
    }

    final public function posAttribute(string $attribute): VespaQuery
    {
        return $this->pos('attribute', $attribute);
    }

    final public function rankingProfile(string $profile): VespaQuery
    {
        return $this->ranking('profile', $profile);
    }

    final public function rankingSorting(string $sorting): VespaQuery
    {
        return $this->ranking('sorting', $sorting);
    }

    final public function rankingQueryCache(bool $queryCache): VespaQuery
    {
        return $this->ranking('queryCache', $queryCache);
    }

    final public function rankingFreshness(string $freshness): VespaQuery
    {
        return $this->ranking('freshness', $freshness);
    }

    final public function rankingMatchPhaseMaxHits($maxHits): VespaQuery
    {
        return $this->rankingMatchPhase('maxHits', $maxHits);
    }

    final public function rankingMatchPhaseAttribute(string $attribute): VespaQuery
    {
        return $this->rankingMatchPhase('attribute', $attribute);
    }

    final public function rankingMatchPhaseAscending(bool $ascending): VespaQuery
    {
        return $this->rankingMatchPhase('ascending', $ascending);
    }

    final public function rankingMatchPhaseDiversityAttribute(string $attribute): VespaQuery
    {
        return $this->rankingMatchPhaseDiversity('attribute', $attribute);
    }

    final public function rankingMatchPhaseDiversityMinGroups($minGroups): VespaQuery
    {
        return $this->rankingMatchPhaseDiversity('minGroups', $minGroups);
    }

    final public function rankingListFeatures(bool $listFeatures): VespaQuery
    {
        return $this->ranking('listFeatures', $listFeatures);
    }

    final public function rankingLocation(string $location): VespaQuery
    {
        return $this->ranking('location', $location);
    }

    final public function rankingFeaturesFeatureName($featurename): VespaQuery
    {
        return $this->rankingFeatures('featurename', $featurename);
    }

    final public function rankingPropertiesPropertyName($propertyname): VespaQuery
    {
        return $this->rankingProperties('properties', $propertyname);
    }

    final public function modelType(string $type): VespaQuery
    {
        return $this->model('type', $type);
    }

    final public function modelRestrict(array $restrict): VespaQuery
    {
        return $this->model('restrict', implode(", ", $restrict));
    }

    final public function modelSources(array $sources): VespaQuery
    {
        return $this->model('sources', implode(", ", $sources));
    }

    final public function modelLanguage(string $language): VespaQuery
    {
        return $this->model('language', $language);
    }

    final public function modelEncoding(string $encoding): VespaQuery
    {
        return $this->model('encoding', $encoding);
    }

    final public function modelQueryString(string $queryString): VespaQuery
    {
        return $this->model('queryString', $queryString);
    }

    final public function modelSearchPath(string $searchPath): VespaQuery
    {
        return $this->model('searchPath', $searchPath);
    }

    final public function modelDefaultIndex(string $defaultIndex): VespaQuery
    {
        return $this->model('defaultIndex', $defaultIndex);
    }

    final public function presentationBolding(bool $bolding): VespaQuery
    {
        return $this->presentation('bolding', $bolding);
    }

    final public function presentationFormat(string $format): VespaQuery
    {
        return $this->presentation('format', $format);
    }

    final public function presentationSummary(string $summary): VespaQuery
    {
        return $this->presentation('summary', $summary);
    }

    final public function presentationTemplate(string $template): VespaQuery
    {
        return $this->presentation('template', $template);
    }

    final public function presentationTiming(bool $timing): VespaQuery
    {
        return $this->presentation('timing', $timing);
    }

    final public function traceTimestamps(bool $timestamps): VespaQuery
    {
        return $this->presentation('timestamps', $timestamps);
    }

    final public function metricsIgnore(bool $ignore): VespaQuery
    {
        return $this->metrics('ignore', $ignore);
    }

    private function rankingProperties($key, $value)
    {
        $this->ranking('properties', [$key => $value]);

        return $this;
    }

    private function rankingMatchPhase($key, $value)
    {
        $this->ranking('matchPhase', [$key => $value]);

        return $this;
    }

    private function rankingMatchPhaseDiversity($key, $value)
    {
        $this->rankingMatchPhase('diversity', [$key => $value]);

        return $this;
    }

    private function rankingFeatures($key, $value)
    {
        $this->ranking('features', [$key => $value]);

        return $this;
    }

    private function ranking($key, $value)
    {
        if (!property_exists($this, 'ranking')) {
            $this->ranking = [];
        }

        $this->ranking[$key] = $value;
        return $this;
    }

    private function model($key, $value)
    {
        if (!property_exists($this, 'model')) {
            $this->model = [];
        }

        $this->model[$key] = $value;
        return $this;
    }

    private function presentation($key, $value)
    {
        if (!property_exists($this, 'presentation')) {
            $this->presentation = [];
        }

        $this->presentation[$key] = $value;
        return $this;
    }

    private function trace($key, $value)
    {
        if (!property_exists($this, 'trace')) {
            $this->trace = [];
        }

        $this->trace[$key] = $value;
        return $this;
    }

    private function metrics($key, $value)
    {
        if (!property_exists($this, 'metrics')) {
            $this->metrics = [];
        }

        $this->metrics[$key] = $value;
        return $this;
    }

    private function collapse($key, $value)
    {
        if (!property_exists($this, 'collapse')) {
            $this->collapse = [];
        }

        $this->collapse[$key] = $value;
        return $this;
    }

    private function pos($key, $value)
    {
        if (!property_exists($this, 'pos')) {
            $this->pos = [];
        }

        $this->pos[$key] = $value;
        return $this;
    }

    private function rules($key, $value)
    {
        if (!property_exists($this, 'rules')) {
            $this->rules = [];
        }

        $this->rules[$key] = $value;
        return $this;
    }

    private function streaming($key, $value)
    {
        if (!property_exists($this, 'streaming')) {
            $this->streaming = [];
        }

        $this->streaming[$key] = $value;
        return $this;
    }
}
