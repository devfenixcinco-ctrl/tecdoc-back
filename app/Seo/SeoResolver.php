<?php

namespace App\Seo;

use App\Models\Manufacturers;
use App\Services\ClientRegistry;
use App\Services\TecdocClient;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Genera título, descripción y contenido de cada ruta del frontend
 * (src/router/index.js) para que el HTML sea legible sin ejecutar JavaScript.
 */
class SeoResolver
{
    private string $client;

    private array $settings;

    public function __construct(private TecdocClient $tecdoc, private ClientRegistry $clients)
    {
    }

    public function resolve(string $path, string $client): PageMeta
    {
        $this->client = $client;
        $this->settings = $this->clients->get($client);

        $s = $path === '' ? [] : array_map('rawurldecode', explode('/', $path));

        return match (true) {
            $s === [] => $this->home(),
            $s === ['catalogo'] => $this->brands(),
            count($s) === 4 && $s[0] === 'catalogo' && $s[1] === 'model' => $this->models($s[2], $s[3]),
            count($s) === 5 && $s[0] === 'catalogo' && $s[1] === 'page' && $s[2] === 'results' => $this->search($s[3]),
            count($s) === 6 && $s[0] === 'catalogo' && $s[1] === 'page' && $s[2] === 'group' && $s[3] === 'results' => $this->search($s[5]),
            count($s) === 4 && $s[0] === 'catalogo' && $s[1] === 'group' && $s[2] === 'results' => $this->search($s[3]),
            count($s) === 2 && $s[0] === 'product' => $this->product($s[1]),
            count($s) === 5 && $s[0] === 'vehicles' => $this->vehicles($s[1], $s[3]),
            count($s) === 8 && $s[0] === 'vehicles' && $s[5] === 'articles' => $this->vehicleArticles($s[1], $s[3], $s[6]),
            count($s) === 11 && $s[0] === 'vehicles' && $s[1] === 'search' && $s[8] === 'articles' => $this->vehicleArticles($s[3], $s[5], $s[9]),
            default => $this->notFound(),
        };
    }

    private function home(): PageMeta
    {
        $title = $this->t('home_title');
        $description = $this->t('home_description');

        return $this->page($title, $description, $this->h1($title).$this->p($description), siteFirst: true);
    }

    private function brands(): PageMeta
    {
        $title = $this->t('brands_title');
        $description = $this->t('brands_description');

        $links = Manufacturers::orderBy('name')->get(['id', 'name'])
            ->map(fn ($brand) => $this->link("/catalogo/model/{$this->segment($brand->name)}/{$brand->id}", $brand->name));

        return $this->page($title, $description, $this->h1($title).$this->p($description).$this->list($links->all()));
    }

    private function models(string $brand, string $brandId): PageMeta
    {
        $title = $this->t('models_title', ['brand' => $brand]);
        $description = $this->t('models_description', ['brand' => $brand]);

        $models = $this->tecdoc('getLinkageTargets', [
            'linkageTargetCountry' => $this->settings['country'],
            'linkageTargetType' => 'VLOB',
            'mfrIds' => $brandId,
            'perPage' => 0,
            'page' => 1,
            'includeVehicleModelSeriesFacets' => true,
        ])['vehicleModelSeriesFacets']['counts'] ?? [];

        $links = collect($models)->sortBy('name')
            ->map(fn ($model) => $this->link(
                "/vehicles/{$this->segment($brand)}/{$brandId}/{$this->segment($model['name'])}/{$model['id']}",
                $model['name']
            ));

        return $this->page($title, $description, $this->h1($title).$this->p($description).$this->list($links->all()));
    }

    private function vehicles(string $brand, string $model): PageMeta
    {
        $vehicle = "$brand $model";
        $title = $this->t('vehicles_title', ['vehicle' => $vehicle]);
        $description = $this->t('vehicles_description', ['vehicle' => $vehicle]);

        return $this->page($title, $description, $this->h1($title).$this->p($description));
    }

    private function vehicleArticles(string $brand, string $model, string $car): PageMeta
    {
        $vehicle = "$brand $model $car";
        $title = $this->t('articles_title', ['vehicle' => $vehicle]);
        $description = $this->t('articles_description', ['vehicle' => $vehicle]);

        return $this->page($title, $description, $this->h1($title).$this->p($description));
    }

    private function search(string $search): PageMeta
    {
        $title = $this->t('search_title', ['search' => $search]);
        $description = $this->t('search_description', ['search' => $search]);

        // Las búsquedas internas no se indexan para no generar páginas duplicadas
        return $this->page($title, $description, $this->h1($title).$this->p($description), robots: 'noindex, follow');
    }

    private function product(string $id): PageMeta
    {
        // Mismos parámetros que ProductosViewOne.vue para aprovechar la caché del proxy
        $article = $this->tecdoc('getArticles', [
            'articleCountry' => $this->settings['country'],
            'searchQuery' => $id,
            'perPage' => 5,
            'page' => 1,
            'includeAll' => true,
        ])['articles'][0] ?? null;

        if (! $article) {
            return $this->page($id, $this->t('home_description'), $this->h1($id));
        }

        // El título usa el tipo de artículo principal; el contenido los incluye todos
        $names = collect($article['genericArticles'] ?? [])->pluck('genericArticleDescription')->filter()->unique();
        $replace = ['name' => $names->first() ?? '', 'number' => $article['articleNumber'] ?? $id, 'brand' => $article['mfrName'] ?? ''];

        $criteria = collect($article['articleCriteria'] ?? [])
            ->filter(fn ($c) => isset($c['criteriaDescription'], $c['formattedValue']))
            ->map(fn ($c) => "{$c['criteriaDescription']}: {$c['formattedValue']}");

        $title = $this->t('product_title', $replace);
        $description = $this->t('product_description', $replace).' '.$criteria->take(4)->join('. ');

        $content = $this->h1(trim("{$replace['name']} {$replace['number']}"))
            .$this->p($this->t('product_brand', $replace))
            .($names->count() > 1 ? $this->p($names->join(', ')) : '')
            .$this->list($criteria->map(fn ($c) => e($c))->all());

        return $this->page($title, $description, $content);
    }

    private function notFound(): PageMeta
    {
        $title = $this->t('not_found_title');

        return $this->page($title, $this->t('home_description'), $this->h1($title), robots: 'noindex, follow', status: 404);
    }

    private function page(string $title, string $description, string $content, bool $siteFirst = false, string $robots = 'index, follow', int $status = 200): PageMeta
    {
        $site = $this->settings['name'];

        return new PageMeta(
            title: $siteFirst ? "$site | $title" : "$title | $site",
            description: Str::limit(trim($description), 160),
            content: '<div class="seo-content">'.$content.'</div>',
            robots: $robots,
            status: $status,
        );
    }

    /**
     * Consulta a TecDoc; si falla la página se genera solo con los datos de la URL.
     */
    private function tecdoc(string $method, array $params): array
    {
        try {
            return $this->tecdoc->call($this->client, $method, $params);
        } catch (RuntimeException $e) {
            return [];
        }
    }

    private function t(string $key, array $replace = []): string
    {
        return __("seo.$key", $replace + ['site' => $this->settings['name']], $this->settings['lang']);
    }

    private function segment(string $value): string
    {
        return rawurlencode($value);
    }

    private function h1(string $text): string
    {
        return '<h1>'.e($text).'</h1>';
    }

    private function p(string $text): string
    {
        return '<p>'.e($text).'</p>';
    }

    private function link(string $href, string $text): string
    {
        return '<a href="'.e($href).'">'.e($text).'</a>';
    }

    /**
     * @param  string[]  $items  HTML ya escapado
     */
    private function list(array $items): string
    {
        return $items ? '<ul><li>'.implode('</li><li>', $items).'</li></ul>' : '';
    }
}
