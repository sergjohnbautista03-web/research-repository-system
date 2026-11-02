<?php

namespace Tests\Feature;

use App\Models\Research;
use DOMDocument;
use DOMXPath;
use Tests\Concerns\JournalWorkflowSchema;
use Tests\TestCase;

class HomeFiltersTest extends TestCase
{
    use JournalWorkflowSchema;

    public function test_home_responses_provide_ajax_results_and_preserve_filters_in_pagination(): void
    {
        $this->createJournalWorkflowSchema();

        for ($index = 1; $index <= 13; $index++) {
            Research::create([
                'title' => "Technology paper {$index}", 'author_name' => 'Research Author',
                'department' => 'College of Computer Studies', 'year_published' => 2026,
                'status' => Research::STATUS_APPROVED,
            ]);
        }

        $query = ['search' => 'Technology', 'department' => 'College of Computer Studies', 'year_from' => 2022];
        $response = $this->get('/?'.http_build_query($query))->assertOk();
        $response->assertSee('js/home-filters.js', false)->assertSee('13 papers found');
        $document = new DOMDocument();
        @$document->loadHTML($response->getContent());
        $xpath = new DOMXPath($document);
        $this->assertSame(1, $xpath->query('//*[@id="home-filters"]')->length);
        $this->assertSame(12, $xpath->query('//*[@id="home-results-content"]//*[@data-search-label]')->length);
        $this->assertSame(0, $xpath->query('//*[@id="home-clear-filters"]/@hidden')->length);

        $links = $xpath->query('//*[@id="home-results-content"]//a[contains(@href, "page=2")]');
        $this->assertGreaterThan(0, $links->length);
        parse_str(parse_url($links->item(0)->getAttribute('href'), PHP_URL_QUERY), $parameters);
        $this->assertSame('Technology', $parameters['search']);
        $this->assertSame('College of Computer Studies', $parameters['department']);
        $this->assertSame('2022', $parameters['year_from']);

        $this->get('/?'.http_build_query($query + ['page' => 2]))
            ->assertOk()->assertViewHas('researches', fn ($papers) => $papers->currentPage() === 2 && $papers->count() === 1);
        $this->get('/?search=NoMatchingPaper')->assertOk()->assertSee('No results found');
        $this->get('/')->assertOk()->assertSee('Featured Research')->assertSee('id="home-results-content"', false);
    }
}
