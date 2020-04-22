<?php
/**
 * Class untuk melakukan pengecekan nomor porsi haji dengan melakukan
 * web scrapping dari https://haji.kemenag.go.id/v4/node/955358.
 *
 * <code>
 *   <?php
 *   use mrlinkerrorsystem\Kemenag\NomorHajiParser;
 *
 *   $scraper = NomorHajiscraper::create($config);
 *   $nomor = NomorHajiChecker::create($scraper);
 *   $nomor->parse();
 *   // Output
 *   {
 *       "porsi": "3000837xxx",
 *       "nama": "Pace Usa Gans",
 *       "kabupaten_kota": "Kota Surabaya",
 *       "provinsi": "Jawa Timur",
 *       "kuota": 15660,
 *       "posisi_porsi_kuota": 86863,
 *       "perkiraan_tahun_berangkat_hijriah": 1444,
 *       "perkiraan_tahun_berangkat_masehi": 2023
 *   }
 * </code>
 *
 * @author      Pace Usa Gans <developerpaceusa@gmail.com>
 * @copyright   2020 Pace Usa Gans <developerpaceusa@gmail.com>
 * @category    Library
 * @license     MIT License <https://opensource.org/licenses/MIT>
 */
namespace mrlinkerrorsystem\Kemenag;
use DomDocument;
use DomXPath;

class NomorHajiParser
{
    /**
     * Versi dari pustaka.
     *
     * @var string
     */
    const VERSION = '1.0';

    /**
     * Objek scraper yang digunakan untuk melakukan fetch data pada
     * website kemenag.
     *
     * @var mrlinkerrorsystem\Kemenag\NomorHajiscraper
     */
    protected $scraper = null;

    /**
     * Constructor
     *
     * @param mrlinkerrorsystem\Kemenag\NomorHajiscraper
     * @return void
     */
    public function __construct(NomorHajiScraper $scraper)
    {
        $this->scraper = $scraper;
    }

    /**
     * @return NomorHajiParser
     */
    public static function create(NomorHajiScraper $scraper)
    {
        return new static($scraper);
    }

    /**
     * @return string JSON
     */
    public function parse()
    {
        $json = [
            'nomor_porsi' => '',
            'nama' => '',
            'kabupaten_kota' => '',
            'provinsi' => '',
            'kuota' => '',
            'posisi_porsi_kuota' => '',
            'perkiraan_tahun_berangkat_hijriah' => '',
            'perkiraan_tahun_berangkat_masehi' => ''
        ];

        try {
            $contents = $this->scraper->getContents();
        } catch (\Exception $e) {
            $json = [
                'error' => $e->getMessage()
            ];

            return json_encode($json);
        }

        libxml_use_internal_errors(true);
        $dom = new DomDocument();
        $dom->preserveWhiteSpace = false;
        $dom->strictErrorChecking = false;
        $dom->loadHtml($contents, LIBXML_NOERROR | LIBXML_NOWARNING);

        $xpath = new DomXPath($dom);
        $porsiNode = $xpath->query('//span[@class="views-label views-label-text-1"]/following-sibling::strong');

        if ($porsiNode->length === 0) {
            return json_encode($json, JSON_PRETTY_PRINT);
        }

        $json['nomor_porsi'] = trim($porsiNode->item(0)->textContent);
        $currentNode = $porsiNode->item(0)->parentNode;

        foreach(array_keys($json) as $key) {
            if ($key === 'nomor_porsi') { continue; }

            $currentNode = $currentNode->nextSibling->nextSibling;
            $json[$key] = trim($currentNode->childNodes->item(3)->textContent);
        }

        return json_encode($json, JSON_PRETTY_PRINT);
    }
}
