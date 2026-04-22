<?php
// app/Controllers/ResourceController.php
declare(strict_types=1);

namespace Controllers;

use Core\Database;
use Core\Request;
use Core\Response;
use Core\Session;
use Helpers\Isbn;
use Services\PdfService;

final class ResourceController extends BaseController
{
    private PdfService $pdfService;
    private const RESOURCE_TYPES = ['book', 'ebook', 'journal', 'thesis', 'other'];
    private const BOOK_TYPES = ['physical', 'digital', 'journal', 'thesis', 'other'];
    private ?array $resourcesTableColumns = null;

    public function __construct()
    {
        parent::__construct();
        $this->pdfService = new PdfService();
    }

    public function index(Request $request): Response
    {
        $authUser = $this->resolveAuthUser();
        if ($authUser === null) {
            Session::destroy();
            return Response::redirect(BASE_URL . '/login');
        }

        $books = $this->resourcesCatalogData();

        $settings = $this->panelSettings();

        return Response::html($this->view->render('admin/resources/index', [
            'title' => 'Recursos - ' . ($settings['library_name'] ?? 'Biblioteca'),
            'settings' => $settings,
            'auth_user' => $authUser,
            'books' => $books,
            'stats' => [
                'total_titles' => count($books),
                'total_copies' => array_sum(array_map(fn(array $b): int => $b['copies'], $books)),
                'available' => array_sum(array_map(fn(array $b): int => $b['available'], $books)),
                'borrowed' => array_sum(array_map(fn(array $b): int => ($b['copies'] - $b['available']), $books)),
            ],
        ], 'layouts/panel'));
    }

    public function create(Request $request): Response
    {
        $authUser = $this->resolveAuthUser();
        if ($authUser === null) {
            Session::destroy();
            return Response::redirect(BASE_URL . '/login');
        }

        $settings = $this->panelSettings();

        $categories = $this->db->query(
            'SELECT id, name FROM categories ORDER BY name ASC'
        )->fetchAll();

        $branches = $this->db->query(
            "SELECT id, name FROM library_branches WHERE status = 'active' ORDER BY is_main DESC, sort_order ASC, name ASC"
        )->fetchAll();

        $old = Session::getFlash('book_form_old', [
            'isbn' => '',
            'marc_leader' => '00000nam a2200000 i 4500',
            'marc_001' => '',
            'marc_020' => '',
            'marc_041' => 'spa',
            'marc_100' => '',
            'marc_245a' => '',
            'marc_245b' => '',
            'marc_250a' => '',
            'marc_260b' => '',
            'marc_260c' => '',
            'marc_300a' => '',
            'marc_520a' => '',
            'marc_650a' => '',
            'marc_700a' => '',
            'marc_856u' => '',
            'title' => '',
            'authors' => '',
            'publisher' => '',
            'edition_statement' => '',
            'publication_year' => '',
            'category_id' => '',
            'branch_id' => '',
            'support_type' => 'physical',
            'resource_type' => 'book',
            'content_type' => 'texto',
            'media_type' => 'sin mediacion',
            'carrier_type' => 'volumen',
            'description' => '',
            'language' => 'es',
            'cover_image' => '',
            'location' => '',
            'digital_url' => '',
            'acquisition_price' => '',
            'replacement_cost' => '',
            'acquisition_date' => date('Y-m-d'),
            'total_copies' => '1',
            'is_new_acquisition' => '1',
        ]);

        return Response::html($this->view->render('admin/resources/create', [
            'title' => 'Nuevo recurso - ' . ($settings['library_name'] ?? 'Biblioteca'),
            'settings' => $settings,
            'auth_user' => $authUser,
            'categories' => $categories,
            'branches' => $branches,
            'old' => $old,
        ], 'layouts/panel'));
    }

    public function store(Request $request): Response
    {
        $authUser = $this->resolveAuthUser();
        if ($authUser === null) {
            Session::destroy();
            return Response::redirect(BASE_URL . '/login');
        }

        $marcLeader = trim((string) $request->post('marc_leader', '00000nam a2200000 i 4500'));
        $marc001 = trim((string) $request->post('marc_001', ''));
        $marc020 = trim((string) $request->post('marc_020', ''));
        $marc041 = trim((string) $request->post('marc_041', ''));
        $marc100 = trim((string) $request->post('marc_100', ''));
        $marc245a = trim((string) $request->post('marc_245a', ''));
        $marc245b = trim((string) $request->post('marc_245b', ''));
        $marc250a = trim((string) $request->post('marc_250a', ''));
        $marc260b = trim((string) $request->post('marc_260b', ''));
        $marc260c = trim((string) $request->post('marc_260c', ''));
        $marc300a = trim((string) $request->post('marc_300a', ''));
        $marc520a = trim((string) $request->post('marc_520a', ''));
        $marc650a = trim((string) $request->post('marc_650a', ''));
        $marc700a = trim((string) $request->post('marc_700a', ''));
        $marc856u = trim((string) $request->post('marc_856u', ''));

        $isbnInput = trim((string) $request->post('isbn', ''));
        if ($isbnInput === '' && $marc020 !== '') {
            $isbnInput = $marc020;
        }
        $isbn13 = $isbnInput !== '' ? Isbn::normalize($isbnInput) : null;

        $title = trim((string) $request->post('title', ''));
        if ($title === '' && $marc245a !== '') {
            $title = $marc245a . ($marc245b !== '' ? ' : ' . $marc245b : '');
        }

        $authorsInput = trim((string) $request->post('authors', ''));
        if ($authorsInput === '' && ($marc100 !== '' || $marc700a !== '')) {
            $authorsInput = trim($marc100 . ($marc700a !== '' ? ', ' . $marc700a : ''));
        }

        $publisher = trim((string) $request->post('publisher', ''));
        if ($publisher === '' && $marc260b !== '') {
            $publisher = $marc260b;
        }

        $editionStatement = trim((string) $request->post('edition_statement', ''));
        if ($editionStatement === '' && $marc250a !== '') {
            $editionStatement = $marc250a;
        }

        $publicationYear = trim((string) $request->post('publication_year', ''));
        if ($publicationYear === '' && $marc260c !== '') {
            $publicationYear = $this->extractYearFromMarcDate($marc260c);
        }
        $categoryId = (int) $request->post('category_id', 0);
        $branchId = (int) $request->post('branch_id', 0);
        $bookType = trim((string) $request->post('support_type', 'physical'));
        $resourceType = trim((string) $request->post('resource_type', 'book'));
        $contentType = trim((string) $request->post('content_type', ''));
        $mediaType = trim((string) $request->post('media_type', ''));
        $carrierType = trim((string) $request->post('carrier_type', ''));
        $description = trim((string) $request->post('description', ''));
        $language = trim((string) $request->post('language', 'es'));
        if (($language === '' || strlen($language) < 2) && $marc041 !== '') {
            $language = substr($marc041, 0, 2);
        }
        $coverImageFile = $request->file('cover_image');
        $coverImage = null;
        if ($coverImageFile !== null && (int)($coverImageFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $uploadError = $this->validateCoverImageFile($coverImageFile);
            if ($uploadError !== null) {
                Session::flash('error', $uploadError);
                return Response::redirect(BASE_URL . '/admin/resources/create');
            }
            $coverImage = $this->storeCoverImage($coverImageFile);
        }
        $location = trim((string) $request->post('location', ''));
        $digitalUrl = trim((string) $request->post('digital_url', ''));
        if ($digitalUrl === '' && $marc856u !== '') {
            $digitalUrl = $marc856u;
        }
        $acquisitionPrice = trim((string) $request->post('acquisition_price', ''));
        $replacementCost = trim((string) $request->post('replacement_cost', ''));
        $acquisitionDate = trim((string) $request->post('acquisition_date', ''));
        $totalCopies = (int) $request->post('total_copies', 0);
        $isNewAcquisition = $request->post('is_new_acquisition', '0') === '1' ? 1 : 0;
        if ($description === '' && $marc520a !== '') {
            $description = $marc520a;
        }

        $old = [
            'isbn' => $isbnInput,
            'marc_leader' => $marcLeader,
            'marc_001' => $marc001,
            'marc_020' => $marc020,
            'marc_041' => $marc041,
            'marc_100' => $marc100,
            'marc_245a' => $marc245a,
            'marc_245b' => $marc245b,
            'marc_250a' => $marc250a,
            'marc_260b' => $marc260b,
            'marc_260c' => $marc260c,
            'marc_300a' => $marc300a,
            'marc_520a' => $marc520a,
            'marc_650a' => $marc650a,
            'marc_700a' => $marc700a,
            'marc_856u' => $marc856u,
            'title' => $title,
            'authors' => $authorsInput,
            'publisher' => $publisher,
            'edition_statement' => $editionStatement,
            'publication_year' => $publicationYear,
            'category_id' => (string) $categoryId,
            'branch_id' => (string) $branchId,
            'support_type' => $bookType,
            'resource_type' => $resourceType,
            'content_type' => $contentType,
            'media_type' => $mediaType,
            'carrier_type' => $carrierType,
            'description' => $description,
            'language' => $language,
            'cover_image' => $coverImage,
            'location' => $location,
            'digital_url' => $digitalUrl,
            'acquisition_price' => $acquisitionPrice,
            'replacement_cost' => $replacementCost,
            'acquisition_date' => $acquisitionDate,
            'total_copies' => (string) $totalCopies,
            'is_new_acquisition' => (string) $isNewAcquisition,
        ];

        $errors = [];

        if ($isbnInput !== '' && $isbn13 === null) {
            $errors[] = 'El ISBN no es válido.';
        }
        if ($title === '') {
            $errors[] = 'El título del recurso es obligatorio.';
        }
        if ($authorsInput === '') {
            $errors[] = 'La autoría o responsabilidad principal es obligatoria.';
        }
        if ($categoryId <= 0) {
            $errors[] = 'Selecciona una categoría.';
        }
        if (!in_array($bookType, self::BOOK_TYPES, true)) {
            $errors[] = 'El tipo de soporte no es válido.';
        }
        if (!in_array($resourceType, self::RESOURCE_TYPES, true)) {
            $errors[] = 'El tipo de recurso no es válido.';
        }
        if ($bookType !== 'digital' && $totalCopies <= 0) {
            $errors[] = 'Las copias totales deben ser al menos 1.';
        }
        if ($bookType === 'digital' && $digitalUrl === '') {
            $errors[] = 'Debes ingresar la URL del recurso digital.';
        }
        if ($replacementCost === '' || !is_numeric($replacementCost) || (float) $replacementCost < 0) {
            $errors[] = 'El costo de reposición es obligatorio y debe ser válido.';
        }
        if ($publicationYear !== '' && (!ctype_digit($publicationYear) || (int) $publicationYear < 1000 || (int) $publicationYear > (int) date('Y') + 1)) {
            $errors[] = 'El año de publicación no es válido.';
        }
        if ($acquisitionDate !== '' && strtotime($acquisitionDate) === false) {
            $errors[] = 'La fecha de adquisición no es válida.';
        }

        if ($isbn13 !== null) {
            $existingStmt = $this->db->prepare('SELECT id FROM resources WHERE isbn_13 = ? LIMIT 1');
            $existingStmt->execute([$isbn13]);
            if ($existingStmt->fetch()) {
                $errors[] = 'Ya existe un libro con ese ISBN.';
            }
        }

        if ($errors !== []) {
            Session::flash('book_form_old', $old);
            Session::flash('error', implode(' ', $errors));
            return Response::redirect(BASE_URL . '/admin/resources/create');
        }

        $authors = array_values(array_filter(array_map(
            static fn(string $author): string => trim($author),
            preg_split('/[,;\n]+/', $authorsInput) ?: []
        )));

        $subjects = array_values(array_filter(array_map(
            static fn(string $subject): string => trim($subject),
            preg_split('/[,;\n]+/', $marc650a) ?: []
        )));

        $addedEntries = array_values(array_filter(array_map(
            static fn(string $entry): string => trim($entry),
            preg_split('/[,;\n]+/', $marc700a) ?: []
        )));

        $marcRecord = $this->buildMarcRecord([
            'leader' => $marcLeader,
            'control_number' => $marc001,
            'isbn_13' => $isbn13,
            'language' => $marc041 !== '' ? $marc041 : $language,
            'main_author' => $marc100 !== '' ? $marc100 : ($authors[0] ?? ''),
            'title' => $marc245a !== '' ? $marc245a : $title,
            'subtitle' => $marc245b,
            'edition' => $marc250a !== '' ? $marc250a : $editionStatement,
            'publisher' => $marc260b !== '' ? $marc260b : $publisher,
            'publication_date' => $marc260c !== '' ? $marc260c : $publicationYear,
            'physical_description' => $marc300a,
            'summary' => $marc520a !== '' ? $marc520a : $description,
            'subjects' => $subjects,
            'added_entries' => $addedEntries,
            'resource_url' => $marc856u !== '' ? $marc856u : $digitalUrl,
            'resource_type' => $resourceType,
            'content_type' => $contentType,
            'media_type' => $mediaType,
            'carrier_type' => $carrierType,
        ]);

        $totalCopies = $bookType === 'digital' ? max(1, $totalCopies) : $totalCopies;
        $availableCopies = $totalCopies;

        $stmt = $this->db->prepare(
            'INSERT INTO resources (
                isbn_13, marc_leader, marc_control_number, title, authors, marc_record, publisher, edition_statement, publication_year, category_id, branch_id,
                support_type, resource_type, content_type, media_type, carrier_type, description, language, cover_image, location, digital_url,
                acquisition_price, replacement_cost, acquisition_date, acquired_at,
                is_new_acquisition, total_copies, available_copies, is_active
            ) VALUES (
                :isbn_13, :marc_leader, :marc_control_number, :title, :authors, :marc_record, :publisher, :edition_statement, :publication_year, :category_id, :branch_id,
                :support_type, :resource_type, :content_type, :media_type, :carrier_type, :description, :language, :cover_image, :location, :digital_url,
                :acquisition_price, :replacement_cost, :acquisition_date, :acquired_at,
                :is_new_acquisition, :total_copies, :available_copies, 1
            )'
        );

        $stmt->execute([
            'isbn_13' => $isbn13,
            'marc_leader' => $marcLeader !== '' ? substr($marcLeader, 0, 24) : '00000nam a2200000 i 4500',
            'marc_control_number' => $marc001 !== '' ? $marc001 : null,
            'title' => $title,
            'authors' => json_encode($authors, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'marc_record' => json_encode($marcRecord, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'publisher' => $publisher !== '' ? $publisher : null,
            'edition_statement' => $editionStatement !== '' ? $editionStatement : null,
            'publication_year' => $publicationYear !== '' ? (int) $publicationYear : null,
            'category_id' => $categoryId,
            'branch_id' => $branchId > 0 ? $branchId : null,
            'support_type' => $bookType,
            'resource_type' => $resourceType,
            'content_type' => $contentType !== '' ? $contentType : null,
            'media_type' => $mediaType !== '' ? $mediaType : null,
            'carrier_type' => $carrierType !== '' ? $carrierType : null,
            'description' => $description !== '' ? $description : null,
            'language' => $language !== '' ? $language : 'es',
            'cover_image' => $coverImage !== '' ? $coverImage : null,
            'location' => $location !== '' ? $location : null,
            'digital_url' => $digitalUrl !== '' ? $digitalUrl : null,
            'acquisition_price' => $acquisitionPrice !== '' ? (float) $acquisitionPrice : null,
            'replacement_cost' => (float) $replacementCost,
            'acquisition_date' => $acquisitionDate !== '' ? $acquisitionDate : null,
            'acquired_at' => $acquisitionDate !== '' ? $acquisitionDate . ' 00:00:00' : date('Y-m-d H:i:s'),
            'is_new_acquisition' => $isNewAcquisition,
            'total_copies' => $totalCopies,
            'available_copies' => $availableCopies,
        ]);

        Session::flash('success', 'Recurso registrado correctamente.');
        return Response::redirect(BASE_URL . '/admin/resources');
    }

    public function edit(Request $request, string $id = ''): Response
    {
        $authUser = $this->resolveAuthUser();
        if ($authUser === null) {
            Session::destroy();
            return Response::redirect(BASE_URL . '/login');
        }

        $bookId = (int) $id;
        if ($bookId <= 0) {
            Session::flash('error', 'Libro invalido.');
            return Response::redirect(BASE_URL . '/admin/resources');
        }

        if ($request->get('modal', '') === '1' && $request->get('saved', '') === '1') {
            $payload = Session::getFlash('book_edit_payload', null);
            $jsonPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            return Response::html(
                '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Guardado</title></head><body><script>window.parent.postMessage({type:"book-edit-saved", payload:' . ($jsonPayload ?: 'null') . '},"*");</script></body></html>'
            );
        }

        $editionStatementSelect = $this->resourcesTableHasColumn('edition_statement')
            ? 'b.edition_statement'
            : 'NULL AS edition_statement';
        $resourceTypeSelect = $this->resourcesTableHasColumn('resource_type')
            ? 'b.resource_type'
            : "'book' AS resource_type";
        $contentTypeSelect = $this->resourcesTableHasColumn('content_type')
            ? 'b.content_type'
            : "NULL AS content_type";
        $mediaTypeSelect = $this->resourcesTableHasColumn('media_type')
            ? 'b.media_type'
            : "NULL AS media_type";
        $carrierTypeSelect = $this->resourcesTableHasColumn('carrier_type')
            ? 'b.carrier_type'
            : "NULL AS carrier_type";

        $stmt = $this->db->prepare(
            'SELECT
                b.id,
                b.isbn_13,
                b.marc_leader,
                b.marc_control_number,
                b.title,
                b.authors,
                b.marc_record,
                b.publisher,
                ' . $editionStatementSelect . ',
                b.publication_year,
                b.category_id,
                b.branch_id,
                b.support_type,
                ' . $resourceTypeSelect . ',
                ' . $contentTypeSelect . ',
                ' . $mediaTypeSelect . ',
                ' . $carrierTypeSelect . ',
                b.description,
                b.language,
                b.cover_image,
                b.location,
                b.digital_url,
                b.acquisition_price,
                b.replacement_cost,
                b.acquisition_date,
                b.total_copies,
                b.available_copies,
                b.is_new_acquisition,
                b.is_active
             FROM resources b
             WHERE b.id = ?
             LIMIT 1'
        );
        $stmt->execute([$bookId]);
        $book = $stmt->fetch();

        if (!$book) {
            Session::flash('error', 'No se encontro el libro solicitado.');
            return Response::redirect(BASE_URL . '/admin/resources');
        }

        $authors = (string) ($book['authors'] ?? '');
        if ($authors !== '' && str_starts_with(trim($authors), '[')) {
            $decoded = json_decode($authors, true);
            if (is_array($decoded)) {
                $authors = implode(', ', array_map(static fn(mixed $v): string => trim((string) $v), $decoded));
            }
        }

        $old = [
            'isbn' => (string) ($book['isbn_13'] ?? ''),
            'marc_leader' => (string) ($book['marc_leader'] ?? '00000nam a2200000 i 4500'),
            'marc_001' => (string) ($book['marc_control_number'] ?? ''),
            'marc_020' => (string) ($book['isbn_13'] ?? ''),
            'marc_041' => (string) ($book['language'] ?? 'spa'),
            'marc_100' => '',
            'marc_245a' => (string) ($book['title'] ?? ''),
            'marc_245b' => '',
            'marc_250a' => '',
            'marc_260b' => (string) ($book['publisher'] ?? ''),
            'marc_260c' => (string) ($book['publication_year'] ?? ''),
            'marc_300a' => '',
            'marc_520a' => (string) ($book['description'] ?? ''),
            'marc_650a' => '',
            'marc_700a' => '',
            'marc_856u' => (string) ($book['digital_url'] ?? ''),
            'title' => (string) ($book['title'] ?? ''),
            'authors' => $authors,
            'publisher' => (string) ($book['publisher'] ?? ''),
            'edition_statement' => (string) ($book['edition_statement'] ?? ''),
            'publication_year' => (string) ($book['publication_year'] ?? ''),
            'category_id' => (string) ($book['category_id'] ?? ''),
            'branch_id' => (string) ($book['branch_id'] ?? ''),
            'support_type' => (string) ($book['support_type'] ?? 'physical'),
            'resource_type' => (string) ($book['resource_type'] ?? 'book'),
            'content_type' => (string) ($book['content_type'] ?? ''),
            'media_type' => (string) ($book['media_type'] ?? ''),
            'carrier_type' => (string) ($book['carrier_type'] ?? ''),
            'description' => (string) ($book['description'] ?? ''),
            'language' => (string) ($book['language'] ?? 'es'),
            'cover_image' => (string) ($book['cover_image'] ?? ''),
            'location' => (string) ($book['location'] ?? ''),
            'digital_url' => (string) ($book['digital_url'] ?? ''),
            'acquisition_price' => (string) ($book['acquisition_price'] ?? ''),
            'replacement_cost' => (string) ($book['replacement_cost'] ?? ''),
            'acquisition_date' => (string) ($book['acquisition_date'] ?? ''),
            'total_copies' => (string) ($book['total_copies'] ?? '1'),
            'available_copies' => (string) ($book['available_copies'] ?? '0'),
            'is_new_acquisition' => (string) ((int) ($book['is_new_acquisition'] ?? 0)),
            'is_active' => (string) ((int) ($book['is_active'] ?? 1)),
        ];

        $marcRaw = (string) ($book['marc_record'] ?? '');
        if ($marcRaw !== '') {
            $marcDecoded = json_decode($marcRaw, true);
            if (is_array($marcDecoded)) {
                $dataFields = is_array($marcDecoded['datafields'] ?? null) ? $marcDecoded['datafields'] : [];
                $old['marc_100'] = (string) (($dataFields['100']['a'] ?? '') ?: $old['marc_100']);
                $old['marc_245a'] = (string) (($dataFields['245']['a'] ?? '') ?: $old['marc_245a']);
                $old['marc_245b'] = (string) (($dataFields['245']['b'] ?? '') ?: $old['marc_245b']);
                $old['marc_250a'] = (string) (($dataFields['250']['a'] ?? '') ?: $old['marc_250a']);
                $old['marc_260b'] = (string) (($dataFields['260']['b'] ?? '') ?: $old['marc_260b']);
                $old['marc_260c'] = (string) (($dataFields['260']['c'] ?? '') ?: $old['marc_260c']);
                $old['marc_300a'] = (string) (($dataFields['300']['a'] ?? '') ?: $old['marc_300a']);
                $old['marc_520a'] = (string) (($dataFields['520']['a'] ?? '') ?: $old['marc_520a']);
                $old['marc_856u'] = (string) (($dataFields['856']['u'] ?? '') ?: $old['marc_856u']);
                $old['marc_650a'] = isset($dataFields['650']['a']) && is_array($dataFields['650']['a'])
                    ? implode(', ', array_map(static fn(mixed $v): string => trim((string) $v), $dataFields['650']['a']))
                    : (string) ($old['marc_650a'] ?? '');
                $old['marc_700a'] = isset($dataFields['700']['a']) && is_array($dataFields['700']['a'])
                    ? implode(', ', array_map(static fn(mixed $v): string => trim((string) $v), $dataFields['700']['a']))
                    : (string) ($old['marc_700a'] ?? '');

                $controlFields = is_array($marcDecoded['controlfields'] ?? null) ? $marcDecoded['controlfields'] : [];
                if ((string) ($controlFields['001'] ?? '') !== '') {
                    $old['marc_001'] = (string) $controlFields['001'];
                }
                if ((string) ($controlFields['020'] ?? '') !== '') {
                    $old['marc_020'] = (string) $controlFields['020'];
                }
                if ((string) ($controlFields['041'] ?? '') !== '') {
                    $old['marc_041'] = (string) $controlFields['041'];
                }
            }
        }

        $flashOld = Session::getFlash('book_form_old', []);
        if (is_array($flashOld) && $flashOld !== []) {
            $old = array_merge($old, $flashOld);
        }

        $categories = $this->db->query('SELECT id, name FROM categories ORDER BY name ASC')->fetchAll();
        $branches = $this->db->query(
            "SELECT id, name FROM library_branches WHERE status = 'active' ORDER BY is_main DESC, sort_order ASC, name ASC"
        )->fetchAll();

        $settings = $this->panelSettings();

        return Response::html($this->view->render('admin/resources/edit', [
            'title' => 'Editar recurso - ' . ($settings['library_name'] ?? 'Biblioteca'),
            'settings' => $settings,
            'auth_user' => $authUser,
            'resource_id' => $bookId,
            'categories' => $categories,
            'branches' => $branches,
            'old' => $old,
        ], $request->get('modal', '') === '1' ? 'layouts/modal' : 'layouts/panel'));
    }

    public function update(Request $request, string $id = ''): Response
    {
        $isModalRequest = $request->post('modal', '') === '1' || $request->get('modal', '') === '1';
        $authUser = $this->resolveAuthUser();
        if ($authUser === null) {
            Session::destroy();
            return Response::redirect(BASE_URL . '/login');
        }

        $bookId = (int) $id;
        if ($bookId <= 0) {
            Session::flash('error', 'Libro invalido.');
            return Response::redirect(BASE_URL . '/admin/resources');
        }

        if (
            !$this->resourcesTableHasColumn('edition_statement')
            || !$this->resourcesTableHasColumn('resource_type')
            || !$this->resourcesTableHasColumn('content_type')
            || !$this->resourcesTableHasColumn('media_type')
            || !$this->resourcesTableHasColumn('carrier_type')
        ) {
            Session::flash('error', 'Falta aplicar la migración de recursos RDA antes de editar con el nuevo formulario.');
            return Response::redirect(
                $isModalRequest
                    ? BASE_URL . '/admin/resources/' . $bookId . '/edit?modal=1'
                    : BASE_URL . '/admin/resources'
            );
        }

        $existsStmt = $this->db->prepare('SELECT id FROM resources WHERE id = ? LIMIT 1');
        $existsStmt->execute([$bookId]);
        if (!$existsStmt->fetch()) {
            Session::flash('error', 'No se encontro el libro solicitado.');
            return Response::redirect(BASE_URL . '/admin/resources');
        }

        $marcLeader = trim((string) $request->post('marc_leader', '00000nam a2200000 i 4500'));
        $marc001 = trim((string) $request->post('marc_001', ''));
        $marc020 = trim((string) $request->post('marc_020', ''));
        $marc041 = trim((string) $request->post('marc_041', ''));
        $marc100 = trim((string) $request->post('marc_100', ''));
        $marc245a = trim((string) $request->post('marc_245a', ''));
        $marc245b = trim((string) $request->post('marc_245b', ''));
        $marc250a = trim((string) $request->post('marc_250a', ''));
        $marc260b = trim((string) $request->post('marc_260b', ''));
        $marc260c = trim((string) $request->post('marc_260c', ''));
        $marc300a = trim((string) $request->post('marc_300a', ''));
        $marc520a = trim((string) $request->post('marc_520a', ''));
        $marc650a = trim((string) $request->post('marc_650a', ''));
        $marc700a = trim((string) $request->post('marc_700a', ''));
        $marc856u = trim((string) $request->post('marc_856u', ''));

        $isbnInput = trim((string) $request->post('isbn', ''));
        if ($isbnInput === '' && $marc020 !== '') {
            $isbnInput = $marc020;
        }
        $isbn13 = $isbnInput !== '' ? Isbn::normalize($isbnInput) : null;

        $title = trim((string) $request->post('title', ''));
        if ($title === '' && $marc245a !== '') {
            $title = $marc245a . ($marc245b !== '' ? ' : ' . $marc245b : '');
        }

        $authorsInput = trim((string) $request->post('authors', ''));
        if ($authorsInput === '' && ($marc100 !== '' || $marc700a !== '')) {
            $authorsInput = trim($marc100 . ($marc700a !== '' ? ', ' . $marc700a : ''));
        }

        $publisher = trim((string) $request->post('publisher', ''));
        if ($publisher === '' && $marc260b !== '') {
            $publisher = $marc260b;
        }

        $editionStatement = trim((string) $request->post('edition_statement', ''));
        if ($editionStatement === '' && $marc250a !== '') {
            $editionStatement = $marc250a;
        }

        $publicationYear = trim((string) $request->post('publication_year', ''));
        if ($publicationYear === '' && $marc260c !== '') {
            $publicationYear = $this->extractYearFromMarcDate($marc260c);
        }
        $categoryId = (int) $request->post('category_id', 0);
        $branchId = (int) $request->post('branch_id', 0);
        $bookType = trim((string) $request->post('support_type', 'physical'));
        $resourceType = trim((string) $request->post('resource_type', 'book'));
        $contentType = trim((string) $request->post('content_type', ''));
        $mediaType = trim((string) $request->post('media_type', ''));
        $carrierType = trim((string) $request->post('carrier_type', ''));
        $description = trim((string) $request->post('description', ''));
        $language = trim((string) $request->post('language', 'es'));
        if (($language === '' || strlen($language) < 2) && $marc041 !== '') {
            $language = substr($marc041, 0, 2);
        }
        $coverImageFile = $request->file('cover_image');
        $existingCoverImage = trim((string) $request->post('existing_cover_image', ''));
        $coverImage = null;
        if ($coverImageFile !== null && (int)($coverImageFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $uploadError = $this->validateCoverImageFile($coverImageFile);
            if ($uploadError !== null) {
                Session::flash('error', $uploadError);
                return Response::redirect(BASE_URL . '/admin/resources/' . $bookId . '/edit');
            }
            $coverImage = $this->storeCoverImage($coverImageFile);
        } else {
            $coverImage = $existingCoverImage !== '' ? $existingCoverImage : null;
        }
        $location = trim((string) $request->post('location', ''));
        $digitalUrl = trim((string) $request->post('digital_url', ''));
        if ($digitalUrl === '' && $marc856u !== '') {
            $digitalUrl = $marc856u;
        }
        $acquisitionPrice = trim((string) $request->post('acquisition_price', ''));
        $replacementCost = trim((string) $request->post('replacement_cost', ''));
        $acquisitionDate = trim((string) $request->post('acquisition_date', ''));
        $totalCopies = (int) $request->post('total_copies', 0);
        $availableCopies = (int) $request->post('available_copies', 0);
        $isNewAcquisition = $request->post('is_new_acquisition', '0') === '1' ? 1 : 0;
        $isActive = $request->post('is_active', '1') === '1' ? 1 : 0;
        if ($description === '' && $marc520a !== '') {
            $description = $marc520a;
        }

        $old = [
            'isbn' => $isbnInput,
            'marc_leader' => $marcLeader,
            'marc_001' => $marc001,
            'marc_020' => $marc020,
            'marc_041' => $marc041,
            'marc_100' => $marc100,
            'marc_245a' => $marc245a,
            'marc_245b' => $marc245b,
            'marc_250a' => $marc250a,
            'marc_260b' => $marc260b,
            'marc_260c' => $marc260c,
            'marc_300a' => $marc300a,
            'marc_520a' => $marc520a,
            'marc_650a' => $marc650a,
            'marc_700a' => $marc700a,
            'marc_856u' => $marc856u,
            'title' => $title,
            'authors' => $authorsInput,
            'publisher' => $publisher,
            'edition_statement' => $editionStatement,
            'publication_year' => $publicationYear,
            'category_id' => (string) $categoryId,
            'branch_id' => (string) $branchId,
            'support_type' => $bookType,
            'resource_type' => $resourceType,
            'content_type' => $contentType,
            'media_type' => $mediaType,
            'carrier_type' => $carrierType,
            'description' => $description,
            'language' => $language,
            'cover_image' => $coverImage,
            'location' => $location,
            'digital_url' => $digitalUrl,
            'acquisition_price' => $acquisitionPrice,
            'replacement_cost' => $replacementCost,
            'acquisition_date' => $acquisitionDate,
            'total_copies' => (string) $totalCopies,
            'available_copies' => (string) $availableCopies,
            'is_new_acquisition' => (string) $isNewAcquisition,
            'is_active' => (string) $isActive,
        ];

        $errors = [];
        if ($isbnInput !== '' && $isbn13 === null) {
            $errors[] = 'El ISBN no es válido.';
        }
        if ($title === '') {
            $errors[] = 'El título del recurso es obligatorio.';
        }
        if ($authorsInput === '') {
            $errors[] = 'La autoría o responsabilidad principal es obligatoria.';
        }
        if ($categoryId <= 0) {
            $errors[] = 'Selecciona una categoría.';
        }
        if (!in_array($bookType, self::BOOK_TYPES, true)) {
            $errors[] = 'El tipo de soporte no es válido.';
        }
        if (!in_array($resourceType, self::RESOURCE_TYPES, true)) {
            $errors[] = 'El tipo de recurso no es válido.';
        }
        if ($bookType !== 'digital' && $totalCopies <= 0) {
            $errors[] = 'Las copias totales deben ser al menos 1.';
        }
        if ($bookType === 'digital' && $digitalUrl === '') {
            $errors[] = 'Debes ingresar la URL del recurso digital.';
        }
        if ($replacementCost === '' || !is_numeric($replacementCost) || (float) $replacementCost < 0) {
            $errors[] = 'El costo de reposición es obligatorio y debe ser válido.';
        }
        if ($publicationYear !== '' && (!ctype_digit($publicationYear) || (int) $publicationYear < 1000 || (int) $publicationYear > (int) date('Y') + 1)) {
            $errors[] = 'El año de publicación no es válido.';
        }
        if ($acquisitionDate !== '' && strtotime($acquisitionDate) === false) {
            $errors[] = 'La fecha de adquisición no es válida.';
        }
        if ($availableCopies < 0) {
            $errors[] = 'Las copias disponibles no pueden ser negativas.';
        }
        if ($availableCopies > $totalCopies) {
            $errors[] = 'Las copias disponibles no pueden exceder el total.';
        }

        if ($isbn13 !== null) {
            $existingStmt = $this->db->prepare('SELECT id FROM resources WHERE isbn_13 = ? AND id <> ? LIMIT 1');
            $existingStmt->execute([$isbn13, $bookId]);
            if ($existingStmt->fetch()) {
                $errors[] = 'Ya existe otro libro con ese ISBN.';
            }
        }

        if ($errors !== []) {
            Session::flash('book_form_old', $old);
            Session::flash('error', implode(' ', $errors));
            return Response::redirect(BASE_URL . '/admin/resources/' . $bookId . '/edit' . ($isModalRequest ? '?modal=1' : ''));
        }

        $authors = array_values(array_filter(array_map(
            static fn(string $author): string => trim($author),
            preg_split('/[,;\n]+/', $authorsInput) ?: []
        )));

        $subjects = array_values(array_filter(array_map(
            static fn(string $subject): string => trim($subject),
            preg_split('/[,;\n]+/', $marc650a) ?: []
        )));

        $addedEntries = array_values(array_filter(array_map(
            static fn(string $entry): string => trim($entry),
            preg_split('/[,;\n]+/', $marc700a) ?: []
        )));

        $marcRecord = $this->buildMarcRecord([
            'leader' => $marcLeader,
            'control_number' => $marc001,
            'isbn_13' => $isbn13,
            'language' => $marc041 !== '' ? $marc041 : $language,
            'main_author' => $marc100 !== '' ? $marc100 : ($authors[0] ?? ''),
            'title' => $marc245a !== '' ? $marc245a : $title,
            'subtitle' => $marc245b,
            'edition' => $marc250a !== '' ? $marc250a : $editionStatement,
            'publisher' => $marc260b !== '' ? $marc260b : $publisher,
            'publication_date' => $marc260c !== '' ? $marc260c : $publicationYear,
            'physical_description' => $marc300a,
            'summary' => $marc520a !== '' ? $marc520a : $description,
            'subjects' => $subjects,
            'added_entries' => $addedEntries,
            'resource_url' => $marc856u !== '' ? $marc856u : $digitalUrl,
            'resource_type' => $resourceType,
            'content_type' => $contentType,
            'media_type' => $mediaType,
            'carrier_type' => $carrierType,
        ]);

        if ($bookType === 'digital') {
            $totalCopies = max(1, $totalCopies);
            $availableCopies = min($totalCopies, max(0, $availableCopies));
            if ($availableCopies === 0) {
                $availableCopies = $totalCopies;
            }
        }

        $stmt = $this->db->prepare(
            'UPDATE resources
             SET
                isbn_13 = :isbn_13,
                marc_leader = :marc_leader,
                marc_control_number = :marc_control_number,
                title = :title,
                authors = :authors,
                marc_record = :marc_record,
                publisher = :publisher,
                edition_statement = :edition_statement,
                publication_year = :publication_year,
                category_id = :category_id,
                branch_id = :branch_id,
                support_type = :support_type,
                resource_type = :resource_type,
                content_type = :content_type,
                media_type = :media_type,
                carrier_type = :carrier_type,
                description = :description,
                language = :language,
                cover_image = :cover_image,
                location = :location,
                digital_url = :digital_url,
                acquisition_price = :acquisition_price,
                replacement_cost = :replacement_cost,
                acquisition_date = :acquisition_date,
                is_new_acquisition = :is_new_acquisition,
                total_copies = :total_copies,
                available_copies = :available_copies,
                is_active = :is_active,
                updated_at = NOW()
             WHERE id = :id
             LIMIT 1'
        );

        $stmt->execute([
            'isbn_13' => $isbn13,
            'marc_leader' => $marcLeader !== '' ? substr($marcLeader, 0, 24) : '00000nam a2200000 i 4500',
            'marc_control_number' => $marc001 !== '' ? $marc001 : null,
            'title' => $title,
            'authors' => json_encode($authors, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'marc_record' => json_encode($marcRecord, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'publisher' => $publisher !== '' ? $publisher : null,
            'edition_statement' => $editionStatement !== '' ? $editionStatement : null,
            'publication_year' => $publicationYear !== '' ? (int) $publicationYear : null,
            'category_id' => $categoryId,
            'branch_id' => $branchId > 0 ? $branchId : null,
            'support_type' => $bookType,
            'resource_type' => $resourceType,
            'content_type' => $contentType !== '' ? $contentType : null,
            'media_type' => $mediaType !== '' ? $mediaType : null,
            'carrier_type' => $carrierType !== '' ? $carrierType : null,
            'description' => $description !== '' ? $description : null,
            'language' => $language !== '' ? $language : 'es',
            'cover_image' => $coverImage !== '' ? $coverImage : null,
            'location' => $location !== '' ? $location : null,
            'digital_url' => $digitalUrl !== '' ? $digitalUrl : null,
            'acquisition_price' => $acquisitionPrice !== '' ? (float) $acquisitionPrice : null,
            'replacement_cost' => (float) $replacementCost,
            'acquisition_date' => $acquisitionDate !== '' ? $acquisitionDate : null,
            'is_new_acquisition' => $isNewAcquisition,
            'total_copies' => $totalCopies,
            'available_copies' => $availableCopies,
            'is_active' => $isActive,
            'id' => $bookId,
        ]);

        if ($isModalRequest) {
            Session::flash('book_edit_payload', [
                'id' => $bookId,
                'title' => $title,
                'author' => implode(', ', $authors),
                'category' => $this->categoryNameById($categoryId),
                'code' => 'RC-' . str_pad((string) $bookId, 4, '0', STR_PAD_LEFT),
                'copies' => $totalCopies,
                'available' => $availableCopies,
                'status' => $this->inventoryStatus($availableCopies, $totalCopies, $isActive),
                'message' => 'Recurso actualizado correctamente.',
            ]);
            return Response::redirect(BASE_URL . '/admin/resources/' . $bookId . '/edit?modal=1&saved=1');
        }

        Session::flash('success', 'Recurso actualizado correctamente.');

        return Response::redirect(BASE_URL . '/admin/resources');
    }

    public function exportExcel(Request $request): Response
    {
        $authUser = $this->resolveAuthUser();
        if ($authUser === null) {
            Session::destroy();
            return Response::redirect(BASE_URL . '/login');
        }

        $typeParam = trim((string) $request->get('type', ''));
        $type = $this->slugToType($typeParam) ?? (in_array($typeParam, self::RESOURCE_TYPES, true) ? $typeParam : null);
        $books = $this->resourcesCatalogData($type);

        $handle = fopen('php://temp', 'r+');
        fputs($handle, "\xEF\xBB\xBF");
        fputcsv($handle, ['Codigo', 'Titulo', 'Autor', 'Categoria', 'Copias', 'Disponibles', 'Prestados', 'Estado']);

        foreach ($books as $book) {
            $copies = (int) $book['copies'];
            $available = (int) $book['available'];
            fputcsv($handle, [
                (string) $book['code'],
                (string) $book['title'],
                (string) $book['author'],
                (string) $book['category'],
                $copies,
                $available,
                max(0, $copies - $available),
                $this->statusLabel((string) $book['status']),
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        $suffix = $type !== null ? ($this->typeToSlug($type) ?? $type) : 'todos';
        $filename = 'recursos_' . $suffix . '_' . date('Ymd_His') . '.csv';

        return (new Response((string) $csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]));
    }

    public function reportPdf(Request $request): Response
    {
        $authUser = $this->resolveAuthUser();
        if ($authUser === null) {
            Session::destroy();
            return Response::redirect(BASE_URL . '/login');
        }

        $typeParam = trim((string) $request->get('type', ''));
        $type = $this->slugToType($typeParam) ?? (in_array($typeParam, self::RESOURCE_TYPES, true) ? $typeParam : null);
        $books = $this->resourcesCatalogData($type);
        $settings = $this->panelSettings();
        $cfg = $type !== null ? $this->typeConfig($type) : null;

        $rows = [];
        foreach ($books as $book) {
            $copies = (int) $book['copies'];
            $available = (int) $book['available'];
            $rows[] = [
                (string) $book['code'],
                (string) $book['title'],
                (string) $book['author'],
                (string) $book['category'],
                (string) $copies,
                (string) $available,
                (string) max(0, $copies - $available),
                $this->statusLabel((string) $book['status']),
            ];
        }

        $pdf = $this->pdfService->renderSimpleTableReport([
            'library' => (string) ($settings['library_name'] ?? 'Biblioteca'),
            'title' => 'Informe de recursos',
            'subtitle' => $cfg !== null
                ? ('Listado de ' . (string) ($cfg['label_plural'] ?? 'recursos') . ' y disponibilidad.')
                : 'Catalogo general de recursos y disponibilidad.',
            'headers' => ['Codigo', 'Titulo', 'Autor', 'Categoria', 'Copias', 'Disp', 'Prest', 'Estado'],
            'rows' => $rows,
            'generated_at' => date('d/m/Y H:i'),
        ]);

        $suffix = $type !== null ? ($this->typeToSlug($type) ?? $type) : 'todos';
        $filename = 'informe_recursos_' . $suffix . '_' . date('Ymd_His') . '.pdf';

        return new Response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    private function resourcesCatalogData(?string $resourceType = null): array
    {
        $hasResourceType = $this->resourcesTableHasColumn('resource_type');
        $resourceTypeSelect = $hasResourceType ? 'b.resource_type' : "'book' AS resource_type";
        if ($hasResourceType && $resourceType !== null) {
            $stmt = $this->db->prepare(
                "SELECT
                    b.id,
                    b.title,
                    b.authors,
                    {$resourceTypeSelect},
                    b.total_copies,
                    b.available_copies,
                    b.is_active,
                    c.name AS category_name
                 FROM resources b
                 LEFT JOIN categories c ON c.id = b.category_id
                 WHERE b.resource_type = :resource_type
                 ORDER BY b.created_at DESC, b.id DESC
                 LIMIT 200"
            );
            $stmt->execute([':resource_type' => $resourceType]);
            $rows = $stmt->fetchAll();
        } else {
            $rows = $this->db->query(
                "SELECT
                    b.id,
                    b.title,
                    b.authors,
                    {$resourceTypeSelect},
                    b.total_copies,
                    b.available_copies,
                    b.is_active,
                    c.name AS category_name
                 FROM resources b
                 LEFT JOIN categories c ON c.id = b.category_id
                 ORDER BY b.created_at DESC, b.id DESC
                 LIMIT 200"
            )->fetchAll();
        }

        return array_map(function (array $book): array {
            $authors = $book['authors'] ?? '';
            if (is_string($authors) && str_starts_with(trim($authors), '[')) {
                $decoded = json_decode($authors, true);
                $authors = is_array($decoded) ? implode(', ', $decoded) : $authors;
            }

            $copies = (int) ($book['total_copies'] ?? 0);
            $available = (int) ($book['available_copies'] ?? 0);
            $status = 'active';

            if ((int) ($book['is_active'] ?? 0) !== 1 || $available <= 0) {
                $status = 'out';
            } elseif ($available <= 2) {
                $status = 'low';
            }

            return [
                'id' => (int) $book['id'],
                'code' => 'RC-' . str_pad((string) $book['id'], 4, '0', STR_PAD_LEFT),
                'title' => (string) $book['title'],
                'author' => (string) $authors,
                'category' => (string) ($book['category_name'] ?? 'Sin categoría'),
                'resource_type' => (string) ($book['resource_type'] ?? 'book'),
                'copies' => $copies,
                'available' => $available,
                'status' => $status,
            ];
        }, $rows);
    }

    private function categoryNameById(int $categoryId): string
    {
        $stmt = $this->db->prepare('SELECT name FROM categories WHERE id = ? LIMIT 1');
        $stmt->execute([$categoryId]);
        $name = $stmt->fetchColumn();

        return is_string($name) && $name !== '' ? $name : 'Sin categoría';
    }

    private function inventoryStatus(int $availableCopies, int $totalCopies, int $isActive): string
    {
        if ($isActive !== 1 || $availableCopies <= 0) {
            return 'out';
        }

        if ($availableCopies <= 2) {
            return 'low';
        }

        return 'active';
    }

    private function resourcesTableHasColumn(string $column): bool
    {
        if ($this->resourcesTableColumns === null) {
            $columns = $this->db->query('SHOW COLUMNS FROM resources')->fetchAll(\PDO::FETCH_ASSOC);
            $this->resourcesTableColumns = array_map(
                static fn(array $item): string => (string) ($item['Field'] ?? ''),
                $columns
            );
        }

        return in_array($column, $this->resourcesTableColumns, true);
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'active' => 'Disponible',
            'low' => 'Pocas copias',
            'out' => 'Sin stock',
            default => 'Estado',
        };
    }

    private function extractYearFromMarcDate(string $value): string
    {
        if (preg_match('/(1[0-9]{3}|20[0-9]{2}|2100)/', $value, $matches) === 1) {
            return $matches[1];
        }

        return '';
    }

    /**
     * Builds a simplified MARC 21 payload as structured JSON for storage and editing.
     */
    private function buildMarcRecord(array $data): array
    {
        return [
            'leader' => (string) ($data['leader'] ?? '00000nam a2200000 i 4500'),
            'controlfields' => [
                '001' => (string) ($data['control_number'] ?? ''),
                '020' => (string) ($data['isbn_13'] ?? ''),
                '041' => (string) ($data['language'] ?? ''),
            ],
            'datafields' => [
                '100' => ['a' => (string) ($data['main_author'] ?? '')],
                '245' => [
                    'a' => (string) ($data['title'] ?? ''),
                    'b' => (string) ($data['subtitle'] ?? ''),
                ],
                '250' => ['a' => (string) ($data['edition'] ?? '')],
                '260' => [
                    'b' => (string) ($data['publisher'] ?? ''),
                    'c' => (string) ($data['publication_date'] ?? ''),
                ],
                '300' => ['a' => (string) ($data['physical_description'] ?? '')],
                '520' => ['a' => (string) ($data['summary'] ?? '')],
                '650' => ['a' => array_values(array_filter((array) ($data['subjects'] ?? [])))],
                '700' => ['a' => array_values(array_filter((array) ($data['added_entries'] ?? [])))],
                '856' => ['u' => (string) ($data['resource_url'] ?? '')],
            ],
            'rda' => [
                'resource_type' => (string) ($data['resource_type'] ?? ''),
                'content_type' => (string) ($data['content_type'] ?? ''),
                'media_type' => (string) ($data['media_type'] ?? ''),
                'carrier_type' => (string) ($data['carrier_type'] ?? ''),
            ],
        ];
    }

    // =========================================================================
    // TYPE-BASED CRUD
    // =========================================================================

    /** Maps URL slug → resource_type value. */
    private function slugToType(string $slug): ?string
    {
        return match ($slug) {
            'libros'         => 'book',
            'digitales'      => 'ebook',
            'revistas'       => 'journal',
            'tesis'          => 'thesis',
            'otros'          => 'other',
            default          => null,
        };
    }

    /** Maps resource_type value → URL slug. */
    private function typeToSlug(string $type): ?string
    {
        return match ($type) {
            'book'    => 'libros',
            'ebook'   => 'digitales',
            'journal' => 'revistas',
            'thesis'  => 'tesis',
            'other'   => 'otros',
            default   => null,
        };
    }

    /** Returns display/field configuration for a given resource_type. */
    private function typeConfig(string $type): array
    {
        $base = [
            'slug'                    => 'otros',
            'label'                   => 'Otro',
            'label_plural'            => 'Otros',
            'support_type'            => 'other',
            'show_isbn'               => false,
            'show_authors'            => true,
            'authors_label'           => 'Autor(es)',
            'authors_required'        => false,
            'show_publisher'          => true,
            'publisher_label'         => 'Editorial',
            'show_edition'            => false,
            'show_digital_url'        => false,
            'digital_url_required'    => false,
            'show_inventory'          => true,
            'copies_required'         => true,
            'show_replacement_cost'   => true,
            'replacement_cost_required' => false,
            'show_cover'              => true,
            'show_location'           => true,
            'show_branch'             => true,
        ];

        return match ($type) {
            'book' => array_merge($base, [
                'slug'                     => 'libros',
                'label'                    => 'Libro físico',
                'label_plural'             => 'Libros físicos',
                'support_type'             => 'physical',
                'show_isbn'                => true,
                'authors_required'         => true,
                'show_edition'             => true,
                'replacement_cost_required'=> true,
            ]),
            'ebook' => array_merge($base, [
                'slug'                     => 'digitales',
                'label'                    => 'Libro digital',
                'label_plural'             => 'Libros digitales',
                'support_type'             => 'digital',
                'show_isbn'                => true,
                'authors_required'         => true,
                'show_edition'             => true,
                'show_digital_url'         => false,
                'digital_url_required'     => true,
                'show_inventory'           => true,
                'copies_required'          => true,
                'show_replacement_cost'    => true,
                'replacement_cost_required'=> true,
                'show_location'            => true,
                'show_branch'              => true,
            ]),
            'journal' => array_merge($base, [
                'slug'                     => 'revistas',
                'label'                    => 'Revista',
                'label_plural'             => 'Revistas',
                'support_type'             => 'journal',
                'show_authors'             => true,
                'authors_label'            => 'Autor(es)',
                'authors_required'         => false,
                'publisher_label'          => 'Casa publicadora / Congreso',
                'show_digital_url'         => true,
                'digital_url_required'     => false,
                'replacement_cost_required'=> false,
            ]),
            'thesis' => array_merge($base, [
                'slug'                     => 'tesis',
                'label'                    => 'Tesis',
                'label_plural'             => 'Tesis',
                'support_type'             => 'thesis',
                'authors_label'            => 'Autor / Sustentante',
                'authors_required'         => true,
                'publisher_label'          => 'Institución educativa',
                'show_replacement_cost'    => false,
                'replacement_cost_required'=> false,
            ]),
            'other' => array_merge($base, [
                'slug'                     => 'otros',
                'label'                    => 'Otro',
                'label_plural'             => 'Otros recursos',
                'support_type'             => 'other',
                'show_isbn'                => true,
                'show_edition'             => true,
                'show_digital_url'         => true,
            ]),
            default => $base,
        };
    }

    /** List resources filtered by type. */
    public function typeIndex(Request $request, string $slug = ''): Response
    {
        $authUser = $this->resolveAuthUser();
        if ($authUser === null) { Session::destroy(); return Response::redirect(BASE_URL . '/login'); }

        $type = $this->slugToType($slug);
        if ($type === null) {
            Session::flash('error', 'Tipo de recurso no válido.');
            return Response::redirect(BASE_URL . '/admin/resources');
        }

        $cfg      = $this->typeConfig($type);
        $settings = $this->panelSettings();

        $stmt = $this->db->prepare(
            "SELECT r.id,
                    r.title,
                    r.authors,
                    r.publisher,
                    r.publication_year,
                    r.total_copies,
                    r.available_copies,
                    r.is_active,
                    r.isbn_13,
                    r.digital_url,
                    r.cover_image,
                    r.language,
                    COALESCE(c.name, '—') AS category,
                    COALESCE(b.name, '—') AS branch
             FROM resources r
             LEFT JOIN categories c ON c.id = r.category_id
             LEFT JOIN library_branches b ON b.id = r.branch_id
             WHERE r.resource_type = ?
             ORDER BY r.title ASC"
        );
        $stmt->execute([$type]);
        $resources = $stmt->fetchAll();

        // Decode authors JSON for display
        $resources = array_map(static function (array $r): array {
            $a = (string) ($r['authors'] ?? '');
            if ($a !== '' && str_starts_with(trim($a), '[')) {
                $decoded = json_decode($a, true);
                if (is_array($decoded)) {
                    $r['authors'] = implode(', ', array_map(static fn(mixed $v): string => trim((string) $v), $decoded));
                }
            }
            return $r;
        }, $resources);

        $categories = $this->db->query('SELECT id, name FROM categories ORDER BY name ASC')->fetchAll();
        $branches   = $this->db->query("SELECT id, name FROM library_branches WHERE status='active' ORDER BY is_main DESC, sort_order ASC, name ASC")->fetchAll();

        return Response::html($this->view->render('admin/resources/type-list', [
            'title'      => $cfg['label_plural'] . ' — Recursos',
            'settings'   => $settings,
            'auth_user'  => $authUser,
            'cfg'        => $cfg,
            'type'       => $type,
            'slug'       => $slug,
            'resources'  => $resources,
            'categories' => $categories,
            'branches'   => $branches,
        ], 'layouts/panel'));
    }

    /** Show create form for a resource type. */
    public function typeCreate(Request $request, string $slug = ''): Response
    {
        $authUser = $this->resolveAuthUser();
        if ($authUser === null) { Session::destroy(); return Response::redirect(BASE_URL . '/login'); }

        $isModal = $request->get('modal', '') === '1';

        $type = $this->slugToType($slug);
        if ($type === null) {
            Session::flash('error', 'Tipo de recurso no válido.');
            return Response::redirect(BASE_URL . '/admin/resources');
        }

        // Saved via modal: send postMessage to parent
        if ($isModal && $request->get('saved', '') === '1') {
            $payload     = Session::getFlash('type_create_payload', null);
            $jsonPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            return Response::html(
                '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"></head><body>'
                . '<script>window.parent.postMessage({type:"resource-type-created",payload:'
                . ($jsonPayload ?: 'null') . '},"*");</script></body></html>'
            );
        }

        $cfg        = $this->typeConfig($type);
        $settings   = $this->panelSettings();
        $categories = $this->db->query('SELECT id, name FROM categories ORDER BY name ASC')->fetchAll();
        $branches   = $this->db->query("SELECT id, name FROM library_branches WHERE status='active' ORDER BY is_main DESC, sort_order ASC, name ASC")->fetchAll();

        $old = Session::getFlash('type_form_old', $this->defaultFormValues($cfg));

        return Response::html($this->view->render('admin/resources/type-form', [
            'title'      => 'Nuevo ' . $cfg['label'] . ' — Recursos',
            'settings'   => $settings,
            'auth_user'  => $authUser,
            'cfg'        => $cfg,
            'type'       => $type,
            'slug'       => $slug,
            'categories' => $categories,
            'branches'   => $branches,
            'old'        => $old,
            'is_edit'    => false,
            'is_modal'   => $isModal,
            'resource_id'=> null,
        ], $isModal ? 'layouts/modal' : 'layouts/panel'));
    }

    /** Store a new resource of the given type. */
    public function typeStore(Request $request, string $slug = ''): Response
    {
        $authUser = $this->resolveAuthUser();
        if ($authUser === null) { Session::destroy(); return Response::redirect(BASE_URL . '/login'); }

        $isModal = $request->post('modal', '') === '1' || $request->get('modal', '') === '1';
        $isWizardSource = (string) $request->post('form_source', '') === 'wizard';

        $type = $this->slugToType($slug);
        if ($type === null) {
            Session::flash('error', 'Tipo de recurso no válido.');
            return Response::redirect(BASE_URL . '/admin/resources');
        }

        $cfg = $this->typeConfig($type);
        $d   = $this->extractTypeFormData($request, $type, $cfg);

        $coverImageFile = $request->file('cover_image');
        if ($coverImageFile !== null && (int)($coverImageFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $uploadError = $this->validateCoverImageFile($coverImageFile);
            if ($uploadError !== null) {
                Session::flash('type_form_old', $d['raw']);
                Session::flash('error', $uploadError);
                $backUrl = BASE_URL . '/admin/resources/type/' . $slug . '/create';
                if ($isModal) {
                    $backUrl .= '?modal=1';
                } elseif ($isWizardSource) {
                    $backUrl = BASE_URL . '/admin/resources/type/' . $slug;
                }
                return Response::redirect($backUrl);
            }
            $uploaded = $this->storeCoverImage($coverImageFile);
            $d['cover_image'] = $uploaded ?? '';
            $d['raw']['cover_image'] = $d['cover_image'];
        }

        if ($type === 'ebook') {
            $digitalFile = $request->file('digital_pdf');
            if ($digitalFile !== null && (int)($digitalFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $pdfError = $this->validateDigitalPdfFile($digitalFile);
                if ($pdfError !== null) {
                    Session::flash('type_form_old', $d['raw']);
                    Session::flash('error', $pdfError);
                    $backUrl = BASE_URL . '/admin/resources/type/' . $slug . '/create';
                    if ($isModal) {
                        $backUrl .= '?modal=1';
                    } elseif ($isWizardSource) {
                        $backUrl = BASE_URL . '/admin/resources/type/' . $slug;
                    }
                    return Response::redirect($backUrl);
                }

                $storedPdf = $this->storeDigitalPdf($digitalFile);
                if ($storedPdf === null) {
                    Session::flash('type_form_old', $d['raw']);
                    Session::flash('error', 'No se pudo guardar el archivo PDF digital.');
                    $backUrl = BASE_URL . '/admin/resources/type/' . $slug . '/create';
                    if ($isModal) {
                        $backUrl .= '?modal=1';
                    } elseif ($isWizardSource) {
                        $backUrl = BASE_URL . '/admin/resources/type/' . $slug;
                    }
                    return Response::redirect($backUrl);
                }

                $d['digital_url'] = $storedPdf;
                $d['raw']['digital_url'] = $storedPdf;
            }
        }

        $errors = $this->validateTypeData($d, $cfg);

        if ($errors !== []) {
            Session::flash('type_form_old', $d['raw']);
            Session::flash('error', implode(' ', $errors));
            $backUrl = BASE_URL . '/admin/resources/type/' . $slug . '/create';
            if ($isModal) {
                $backUrl .= '?modal=1';
            } elseif ($isWizardSource) {
                $backUrl = BASE_URL . '/admin/resources/type/' . $slug;
            }
            return Response::redirect($backUrl);
        }

        $newId = $this->doInsertResource($d);

        if ($isModal) {
            $authors = array_values(array_filter(array_map(
                static fn(string $a): string => trim($a),
                preg_split('/[,;\n]+/', $d['authors']) ?: []
            )));

            $total = (int) $d['total_copies'];
            $available = (int) $d['available_copies'];
            if (!$cfg['show_inventory']) {
                $available = $total;
            }

            Session::flash('type_create_payload', [
                'id'        => $newId,
                'title'     => $d['title'],
                'author'    => implode(', ', $authors),
                'category'  => $this->categoryNameById((int) $d['category_id']),
                'copies'    => $total,
                'available' => $available,
                'status'    => $this->inventoryStatus($available, $total, 1),
                'message'   => $cfg['label'] . ' registrado correctamente.',
            ]);
            return Response::redirect(BASE_URL . '/admin/resources/type/' . $slug . '/create?modal=1&saved=1');
        }

        Session::flash('success', $cfg['label'] . ' registrado correctamente.');
        return Response::redirect(BASE_URL . '/admin/resources/type/' . $slug);
    }


    /** Show edit form for a resource of the given type. */
    public function typeEdit(Request $request, string $slug = '', string $id = ''): Response
    {
        $authUser = $this->resolveAuthUser();
        if ($authUser === null) { Session::destroy(); return Response::redirect(BASE_URL . '/login'); }

        $isModal = $request->get('modal', '') === '1';

        $type = $this->slugToType($slug);
        if ($type === null) {
            Session::flash('error', 'Tipo de recurso no válido.');
            return Response::redirect(BASE_URL . '/admin/resources');
        }

        $resourceId = (int) $id;
        if ($resourceId <= 0) {
            Session::flash('error', 'Recurso inválido.');
            return Response::redirect(BASE_URL . '/admin/resources/type/' . $slug);
        }

        // Saved via modal: send postMessage to parent
        if ($isModal && $request->get('saved', '') === '1') {
            $payload     = Session::getFlash('type_edit_payload', null);
            $jsonPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            return Response::html(
                '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"></head><body>'
                . '<script>window.parent.postMessage({type:"resource-type-saved",payload:'
                . ($jsonPayload ?: 'null') . '},"*");</script></body></html>'
            );
        }

        $stmt = $this->db->prepare(
            "SELECT r.*, COALESCE(r.resource_type,'book') AS resource_type
             FROM resources r WHERE r.id = ? LIMIT 1"
        );
        $stmt->execute([$resourceId]);
        $resource = $stmt->fetch();

        if (!$resource) {
            Session::flash('error', 'Recurso no encontrado.');
            return Response::redirect(BASE_URL . '/admin/resources/type/' . $slug);
        }

        $currentType = (string) ($resource['resource_type'] ?? 'book');
        if ($currentType !== $type) {
            $correctSlug = $this->typeToSlug($currentType) ?? $slug;
            Session::flash('error', 'El recurso solicitado pertenece a otro tipo.');
            $editUrl = BASE_URL . '/admin/resources/type/' . $correctSlug . '/' . $resourceId . '/edit';
            return Response::redirect($isModal ? $editUrl . '?modal=1' : $editUrl);
        }

        $cfg        = $this->typeConfig($type);
        $settings   = $this->panelSettings();
        $categories = $this->db->query('SELECT id, name FROM categories ORDER BY name ASC')->fetchAll();
        $branches   = $this->db->query("SELECT id, name FROM library_branches WHERE status='active' ORDER BY is_main DESC, sort_order ASC, name ASC")->fetchAll();

        $authorsStr = (string) ($resource['authors'] ?? '');
        if ($authorsStr !== '' && str_starts_with(trim($authorsStr), '[')) {
            $decoded = json_decode($authorsStr, true);
            if (is_array($decoded)) {
                $authorsStr = implode(', ', array_map(static fn(mixed $v): string => trim((string) $v), $decoded));
            }
        }

        $old = [
            'title'             => (string) ($resource['title'] ?? ''),
            'authors'           => $authorsStr,
            'isbn'              => (string) ($resource['isbn_13'] ?? ''),
            'publisher'         => (string) ($resource['publisher'] ?? ''),
            'edition_statement' => (string) ($resource['edition_statement'] ?? ''),
            'publication_year'  => (string) ($resource['publication_year'] ?? ''),
            'language'          => (string) ($resource['language'] ?? 'es'),
            'description'       => (string) ($resource['description'] ?? ''),
            'cover_image'       => (string) ($resource['cover_image'] ?? ''),
            'digital_url'       => (string) ($resource['digital_url'] ?? ''),
            'category_id'       => (string) ($resource['category_id'] ?? ''),
            'branch_id'         => (string) ($resource['branch_id'] ?? ''),
            'location'          => (string) ($resource['location'] ?? ''),
            'total_copies'      => (string) ($resource['total_copies'] ?? '1'),
            'available_copies'  => (string) ($resource['available_copies'] ?? '0'),
            'replacement_cost'  => (string) ($resource['replacement_cost'] ?? ''),
            'acquisition_price' => (string) ($resource['acquisition_price'] ?? ''),
            'acquisition_date'  => (string) ($resource['acquisition_date'] ?? ''),
            'is_new_acquisition'=> (string) ((int) ($resource['is_new_acquisition'] ?? 0)),
            'is_active'         => (string) ((int) ($resource['is_active'] ?? 1)),
        ];

        $flashOld = Session::getFlash('type_form_old', []);
        if (is_array($flashOld) && $flashOld !== []) {
            $old = array_merge($old, $flashOld);
        }

        return Response::html($this->view->render('admin/resources/type-form', [
            'title'      => 'Editar ' . $cfg['label'],
            'settings'   => $settings,
            'auth_user'  => $authUser,
            'cfg'        => $cfg,
            'type'       => $type,
            'slug'       => $slug,
            'categories' => $categories,
            'branches'   => $branches,
            'old'        => $old,
            'is_edit'    => true,
            'is_modal'   => $isModal,
            'resource_id'=> $resourceId,
        ], $isModal ? 'layouts/modal' : 'layouts/panel'));
    }

    /** Update a resource of the given type. */
    public function typeUpdate(Request $request, string $slug = '', string $id = ''): Response
    {
        $isModal = $request->post('modal', '') === '1' || $request->get('modal', '') === '1';

        $authUser = $this->resolveAuthUser();
        if ($authUser === null) { Session::destroy(); return Response::redirect(BASE_URL . '/login'); }

        $type = $this->slugToType($slug);
        if ($type === null) {
            Session::flash('error', 'Tipo de recurso no válido.');
            return Response::redirect(BASE_URL . '/admin/resources');
        }

        $resourceId = (int) $id;
        if ($resourceId <= 0) {
            Session::flash('error', 'Recurso inválido.');
            return Response::redirect(BASE_URL . '/admin/resources/type/' . $slug);
        }

        $currentStmt = $this->db->prepare(
            'SELECT id,
                COALESCE(resource_type,\'book\') AS resource_type,
                COALESCE(available_copies, 0) AS available_copies,
                COALESCE(is_active, 1) AS is_active
             FROM resources
             WHERE id = ?
             LIMIT 1'
        );
        $currentStmt->execute([$resourceId]);
        $current = $currentStmt->fetch();

        if (!$current) {
            Session::flash('error', 'Recurso no encontrado.');
            return Response::redirect(BASE_URL . '/admin/resources/type/' . $slug);
        }

        $currentType = (string) ($current['resource_type'] ?? 'book');
        if ($currentType !== $type) {
            $correctSlug = $this->typeToSlug($currentType) ?? $slug;
            Session::flash('error', 'No se pudo actualizar: el recurso pertenece a otro tipo.');
            $editUrl = BASE_URL . '/admin/resources/type/' . $correctSlug . '/' . $resourceId . '/edit';
            return Response::redirect($isModal ? $editUrl . '?modal=1' : $editUrl);
        }

        $cfg    = $this->typeConfig($type);
        $d      = $this->extractTypeFormData($request, $type, $cfg);

        $requestData = $request->all();
        if (!array_key_exists('available_copies', $requestData)) {
            $d['available_copies'] = (int) ($current['available_copies'] ?? 0);
            $d['raw']['available_copies'] = (string) $d['available_copies'];
        }
        if (!array_key_exists('is_active', $requestData)) {
            $d['is_active'] = (int) ($current['is_active'] ?? 1);
            $d['raw']['is_active'] = (string) $d['is_active'];
        }

        $coverImageFile = $request->file('cover_image');
        if ($coverImageFile !== null && (int)($coverImageFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $uploadError = $this->validateCoverImageFile($coverImageFile);
            if ($uploadError !== null) {
                Session::flash('type_form_old', $d['raw']);
                Session::flash('error', $uploadError);
                $editUrl = BASE_URL . '/admin/resources/type/' . $slug . '/' . $resourceId . '/edit';
                return Response::redirect($isModal ? $editUrl . '?modal=1' : $editUrl);
            }
            $uploaded = $this->storeCoverImage($coverImageFile);
            $d['cover_image'] = $uploaded ?? '';
            $d['raw']['cover_image'] = $d['cover_image'];
        } else {
            $existingCoverImage = trim((string) $request->post('existing_cover_image', ''));
            if ($existingCoverImage !== '') {
                $d['cover_image'] = $existingCoverImage;
                $d['raw']['cover_image'] = $existingCoverImage;
            }
        }

        $existingDigitalPath = '';
        $uploadedDigitalPath = '';
        if ($type === 'ebook') {
            $existingDigitalPath = trim((string) $request->post('existing_digital_url', ''));
            if ($existingDigitalPath !== '') {
                $d['digital_url'] = $existingDigitalPath;
                $d['raw']['digital_url'] = $existingDigitalPath;
            }

            $digitalFile = $request->file('digital_pdf');
            if ($digitalFile !== null && (int)($digitalFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $pdfError = $this->validateDigitalPdfFile($digitalFile);
                if ($pdfError !== null) {
                    Session::flash('type_form_old', $d['raw']);
                    Session::flash('error', $pdfError);
                    $editUrl = BASE_URL . '/admin/resources/type/' . $slug . '/' . $resourceId . '/edit';
                    return Response::redirect($isModal ? $editUrl . '?modal=1' : $editUrl);
                }

                $storedPdf = $this->storeDigitalPdf($digitalFile);
                if ($storedPdf === null) {
                    Session::flash('type_form_old', $d['raw']);
                    Session::flash('error', 'No se pudo guardar el archivo PDF digital.');
                    $editUrl = BASE_URL . '/admin/resources/type/' . $slug . '/' . $resourceId . '/edit';
                    return Response::redirect($isModal ? $editUrl . '?modal=1' : $editUrl);
                }

                $uploadedDigitalPath = $storedPdf;

                $d['digital_url'] = $storedPdf;
                $d['raw']['digital_url'] = $storedPdf;
            }
        }

        $errors = $this->validateTypeData($d, $cfg, $resourceId);

        if ($errors !== []) {
            Session::flash('type_form_old', $d['raw']);
            Session::flash('error', implode(' ', $errors));
            $editUrl = BASE_URL . '/admin/resources/type/' . $slug . '/' . $resourceId . '/edit';
            return Response::redirect($isModal ? $editUrl . '?modal=1' : $editUrl);
        }

        $this->doUpdateResource($resourceId, $d);

        if ($type === 'ebook' && $uploadedDigitalPath !== '' && $existingDigitalPath !== '' && $uploadedDigitalPath !== $existingDigitalPath) {
            $this->deleteStoredDigitalPdf($existingDigitalPath);
        }

        if ($isModal) {
            $authors = array_values(array_filter(array_map(
                static fn(string $a): string => trim($a),
                preg_split('/[,;\n]+/', $d['authors']) ?: []
            )));
            Session::flash('type_edit_payload', [
                'id'        => $resourceId,
                'title'     => $d['title'],
                'author'    => implode(', ', $authors),
                'category'  => $this->categoryNameById((int) $d['category_id']),
                'copies'    => (int) $d['total_copies'],
                'available' => (int) $d['available_copies'],
                'status'    => $this->inventoryStatus((int) $d['available_copies'], (int) $d['total_copies'], (int) $d['is_active']),
                'message'   => $cfg['label'] . ' actualizado correctamente.',
            ]);
            return Response::redirect(BASE_URL . '/admin/resources/type/' . $slug . '/' . $resourceId . '/edit?modal=1&saved=1');
        }

        Session::flash('success', $cfg['label'] . ' actualizado correctamente.');
        return Response::redirect(BASE_URL . '/admin/resources/type/' . $slug);
    }

    /** Delete a resource of the given type and remove its stored cover image, if any. */
    public function typeDelete(Request $request, string $slug = '', string $id = ''): Response
    {
        $authUser = $this->resolveAuthUser();
        if ($authUser === null) { Session::destroy(); return Response::redirect(BASE_URL . '/login'); }

        if (!hash_equals(Session::get('_csrf_token', ''), $request->post('_csrf_token', ''))) {
            Session::flash('error', 'Token de seguridad inválido.');
            return Response::redirect(BASE_URL . '/admin/resources/type/' . $slug);
        }

        $type = $this->slugToType($slug);
        if ($type === null) {
            Session::flash('error', 'Tipo de recurso no válido.');
            return Response::redirect(BASE_URL . '/admin/resources');
        }

        $resourceId = (int) $id;
        if ($resourceId <= 0) {
            Session::flash('error', 'Recurso inválido.');
            return Response::redirect(BASE_URL . '/admin/resources/type/' . $slug);
        }

        $stmt = $this->db->prepare(
            'SELECT id, title, COALESCE(resource_type,\'book\') AS resource_type, cover_image, digital_url
             FROM resources WHERE id = ? LIMIT 1'
        );
        $stmt->execute([$resourceId]);
        $resource = $stmt->fetch();

        if (!$resource) {
            Session::flash('error', 'Recurso no encontrado.');
            return Response::redirect(BASE_URL . '/admin/resources/type/' . $slug);
        }

        $currentType = (string) ($resource['resource_type'] ?? 'book');
        if ($currentType !== $type) {
            $correctSlug = $this->typeToSlug($currentType) ?? $slug;
            Session::flash('error', 'El recurso solicitado pertenece a otro tipo.');
            return Response::redirect(BASE_URL . '/admin/resources/type/' . $correctSlug);
        }

        try {
            $delete = $this->db->prepare('DELETE FROM resources WHERE id = ? LIMIT 1');
            $delete->execute([$resourceId]);
        } catch (\PDOException $e) {
            // FK violations (loans/reservations/etc.)
            $sqlState = (string) $e->getCode();
            if ($sqlState === '23000') {
                Session::flash('error', 'No se puede eliminar este recurso porque tiene préstamos, reservas u otros movimientos asociados.');
            } else {
                Session::flash('error', 'No se pudo eliminar el recurso en este momento. Inténtalo nuevamente.');
            }
            return Response::redirect(BASE_URL . '/admin/resources/type/' . $slug);
        } catch (\Throwable $e) {
            Session::flash('error', 'No se pudo eliminar el recurso en este momento. Inténtalo nuevamente.');
            return Response::redirect(BASE_URL . '/admin/resources/type/' . $slug);
        }

        // Non-blocking audit log. Deletion must not fail if audit logging fails.
        try {
            $stmt = $this->db->prepare(
                'INSERT INTO audit_logs (user_id, action, entity_type, entity_id, old_values, new_values, ip_address, user_agent)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            );

            $oldValues = [
                'title' => (string) ($resource['title'] ?? ''),
                'resource_type' => (string) ($resource['resource_type'] ?? ''),
            ];

            $stmt->execute([
                (int) ($authUser['id'] ?? 0) ?: null,
                'resource.delete',
                'resource',
                $resourceId,
                json_encode($oldValues, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                null,
                (string) ($_SERVER['REMOTE_ADDR'] ?? 'cli'),
                mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? 'cli'), 0, 255),
            ]);
        } catch (\Throwable) {
            // Do not block user flow if audit fails.
        }

        $this->deleteStoredCoverImage((string) ($resource['cover_image'] ?? ''));
        $this->deleteStoredDigitalPdf((string) ($resource['digital_url'] ?? ''));

        Session::flash('success', 'Recurso eliminado correctamente.');
        return Response::redirect(BASE_URL . '/admin/resources/type/' . $slug);
    }

    /** Stream a digital PDF only when user has an active reservation for this resource. */
    public function readDigitalResource(Request $request, string $id = ''): Response
    {
        $authUser = $this->resolveAuthUser();
        if ($authUser === null) {
            Session::destroy();
            return Response::redirect(BASE_URL . '/login');
        }

        $resourceId = (int) $id;
        if ($resourceId <= 0) {
            Session::flash('error', 'Recurso digital inválido.');
            return Response::redirect(BASE_URL . '/catalog');
        }

        $stmt = $this->db->prepare(
            'SELECT id, title, support_type, is_active, digital_url
             FROM resources
             WHERE id = ? LIMIT 1'
        );
        $stmt->execute([$resourceId]);
        $resource = $stmt->fetch();

        if (!$resource || (int) ($resource['is_active'] ?? 0) !== 1 || (string) ($resource['support_type'] ?? '') !== 'digital') {
            Session::flash('error', 'El recurso digital no está disponible.');
            return Response::redirect(BASE_URL . '/catalog/' . $resourceId);
        }

                $reservationStmt = $this->db->prepare(
            "SELECT id
             FROM reservations
             WHERE resource_id = ?
               AND user_id = ?
               AND status IN ('waiting', 'notified', 'fulfilled')
             ORDER BY id DESC
             LIMIT 1"
        );
        $reservationStmt->execute([$resourceId, (int) $authUser['id']]);
                $hasReservation = (bool) $reservationStmt->fetch();

                $loanStmt = $this->db->prepare(
                        "SELECT id
                         FROM loans
                         WHERE resource_id = ?
                             AND user_id = ?
                             AND status IN ('active','overdue')
                         ORDER BY id DESC
                         LIMIT 1"
                );
                $loanStmt->execute([$resourceId, (int) $authUser['id']]);
                $hasActiveLoan = (bool) $loanStmt->fetch();

                if (!$hasReservation && !$hasActiveLoan) {
            Session::flash('error', 'Debes reservar este libro digital para poder leerlo.');
            return Response::redirect(BASE_URL . '/catalog/' . $resourceId);
        }

        $relativePath = trim((string) ($resource['digital_url'] ?? ''));
        if ($relativePath === '' || !str_starts_with($relativePath, '/storage/uploads/digital-resources/')) {
            Session::flash('error', 'El archivo PDF del recurso no está disponible.');
            return Response::redirect(BASE_URL . '/catalog/' . $resourceId);
        }

        $storageRoot = realpath(BASE_PATH . '/storage/uploads/digital-resources');
        $absolutePath = realpath(BASE_PATH . $relativePath);
        if ($storageRoot === false || $absolutePath === false || !is_file($absolutePath)) {
            Session::flash('error', 'El archivo PDF del recurso no fue encontrado.');
            return Response::redirect(BASE_URL . '/catalog/' . $resourceId);
        }

        if (!str_starts_with($absolutePath, $storageRoot . DIRECTORY_SEPARATOR)) {
            Session::flash('error', 'Ruta de archivo digital inválida.');
            return Response::redirect(BASE_URL . '/catalog/' . $resourceId);
        }

        try {
            $this->db->prepare('UPDATE resources SET digital_access_count = digital_access_count + 1 WHERE id = ?')
                ->execute([$resourceId]);

            $this->db->prepare(
                "INSERT INTO digital_access_log (resource_id, user_id, action, ip_address, created_at)
                 VALUES (?, ?, 'view', ?, NOW())"
            )->execute([
                $resourceId,
                (int) $authUser['id'],
                (string) ($_SERVER['REMOTE_ADDR'] ?? 'cli'),
            ]);
        } catch (\Throwable) {
            // Non-blocking logging.
        }

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="recurso-digital-' . $resourceId . '.pdf"');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, no-store, max-age=0');
        header('Content-Length: ' . (string) filesize($absolutePath));
        readfile($absolutePath);
        exit;
    }

    // ── Type CRUD helpers ─────────────────────────────────────────────────────

    private function defaultFormValues(array $cfg): array
    {
        return [
            'title'             => '',
            'authors'           => '',
            'isbn'              => '',
            'publisher'         => '',
            'edition_statement' => '',
            'publication_year'  => '',
            'language'          => 'es',
            'description'       => '',
            'cover_image'       => '',
            'digital_url'       => '',
            'category_id'       => '',
            'branch_id'         => '',
            'location'          => '',
            'total_copies'      => '1',
            'available_copies'  => '1',
            'replacement_cost'  => '',
            'acquisition_price' => '',
            'acquisition_date'  => date('Y-m-d'),
            'is_new_acquisition'=> '1',
            'is_active'         => '1',
        ];
    }

    private function extractTypeFormData(Request $request, string $type, array $cfg): array
    {
        $title             = trim((string) $request->post('title', ''));
        $authors           = trim((string) $request->post('authors', ''));
        $isbn              = trim((string) $request->post('isbn', ''));
        $publisher         = trim((string) $request->post('publisher', ''));
        $editionStatement  = trim((string) $request->post('edition_statement', ''));
        $publicationYear   = trim((string) $request->post('publication_year', ''));
        $language          = trim((string) $request->post('language', 'es'));
        $description       = trim((string) $request->post('description', ''));
        $coverImage        = trim((string) $request->post('cover_image', ''));
        $digitalUrl        = trim((string) $request->post('digital_url', ''));
        $categoryId        = (int) $request->post('category_id', 0);
        $branchId          = (int) $request->post('branch_id', 0);
        $location          = trim((string) $request->post('location', ''));
        $totalCopies       = (int) $request->post('total_copies', 1);
        $availableCopies   = (int) $request->post('available_copies', 0);
        $replacementCost   = trim((string) $request->post('replacement_cost', ''));
        $acquisitionPrice  = trim((string) $request->post('acquisition_price', ''));
        $acquisitionDate   = trim((string) $request->post('acquisition_date', ''));
        $isNewAcquisition  = $request->post('is_new_acquisition', '0') === '1' ? 1 : 0;
        $isActive          = $request->post('is_active', '1') === '1' ? 1 : 0;

        // Defaults for non-inventory types
        if (!$cfg['show_inventory']) {
            $totalCopies     = max(1, $totalCopies);
            $availableCopies = $totalCopies;
        }
        if (!$cfg['show_replacement_cost']) {
            $replacementCost = '0';
        }
        if (!$cfg['show_authors']) {
            $authors = '';
        }

        $raw = [
            'title'             => $title,
            'authors'           => $authors,
            'isbn'              => $isbn,
            'publisher'         => $publisher,
            'edition_statement' => $editionStatement,
            'publication_year'  => $publicationYear,
            'language'          => $language,
            'description'       => $description,
            'cover_image'       => $coverImage,
            'digital_url'       => $digitalUrl,
            'category_id'       => (string) $categoryId,
            'branch_id'         => (string) $branchId,
            'location'          => $location,
            'total_copies'      => (string) $totalCopies,
            'available_copies'  => (string) $availableCopies,
            'replacement_cost'  => $replacementCost,
            'acquisition_price' => $acquisitionPrice,
            'acquisition_date'  => $acquisitionDate,
            'is_new_acquisition'=> (string) $isNewAcquisition,
            'is_active'         => (string) $isActive,
        ];

        return [
            'title'             => $title,
            'authors'           => $authors,
            'isbn'              => $isbn,
            'publisher'         => $publisher,
            'edition_statement' => $editionStatement,
            'publication_year'  => $publicationYear,
            'language'          => $language ?: 'es',
            'description'       => $description,
            'cover_image'       => $coverImage,
            'digital_url'       => $digitalUrl,
            'category_id'       => $categoryId,
            'branch_id'         => $branchId,
            'location'          => $location,
            'total_copies'      => $totalCopies,
            'available_copies'  => $availableCopies,
            'replacement_cost'  => $replacementCost,
            'acquisition_price' => $acquisitionPrice,
            'acquisition_date'  => $acquisitionDate,
            'is_new_acquisition'=> $isNewAcquisition,
            'is_active'         => $isActive,
            'resource_type'     => $type,
            'support_type'      => $cfg['support_type'],
            'raw'               => $raw,
        ];
    }

    private function validateTypeData(array $d, array $cfg, ?int $excludeId = null): array
    {
        $errors = [];

        if ($d['title'] === '') {
            $errors[] = 'El título es obligatorio.';
        }
        if ($cfg['authors_required'] && $d['authors'] === '') {
            $errors[] = $cfg['authors_label'] . ' es obligatorio.';
        }
        if ($d['category_id'] <= 0) {
            $errors[] = 'Selecciona una categoría.';
        }
        if ($cfg['digital_url_required'] && $d['digital_url'] === '') {
            $errors[] = (($d['resource_type'] ?? '') === 'ebook')
                ? 'Debes cargar el archivo PDF del libro digital.'
                : 'La URL de acceso digital es obligatoria.';
        }
        if ($cfg['copies_required'] && $d['total_copies'] <= 0) {
            $errors[] = 'Las copias totales deben ser al menos 1.';
        }
        if ($cfg['replacement_cost_required'] && ($d['replacement_cost'] === '' || !is_numeric($d['replacement_cost']) || (float)$d['replacement_cost'] < 0)) {
            $errors[] = 'El costo de reposición es obligatorio.';
        }
        if ($d['publication_year'] !== '' && (!ctype_digit($d['publication_year']) || (int)$d['publication_year'] < 1000 || (int)$d['publication_year'] > (int) date('Y') + 1)) {
            $errors[] = 'El año de publicación no es válido.';
        }
        if ($d['acquisition_date'] !== '' && strtotime($d['acquisition_date']) === false) {
            $errors[] = 'La fecha de adquisición no es válida.';
        }

        // ISBN validation & duplicate check
        if ($d['isbn'] !== '') {
            $isbn13 = Isbn::normalize($d['isbn']);
            if ($isbn13 === null) {
                $errors[] = 'El ISBN ingresado no es válido.';
            } else {
                if ($excludeId !== null) {
                    $chk = $this->db->prepare('SELECT id FROM resources WHERE isbn_13 = ? AND id <> ? LIMIT 1');
                    $chk->execute([$isbn13, $excludeId]);
                } else {
                    $chk = $this->db->prepare('SELECT id FROM resources WHERE isbn_13 = ? LIMIT 1');
                    $chk->execute([$isbn13]);
                }
                if ($chk->fetch()) {
                    $errors[] = 'Ya existe un recurso con ese ISBN.';
                }
            }
        }

        return $errors;
    }

    private function doInsertResource(array $d): int
    {
        $isbn13  = $d['isbn'] !== '' ? Isbn::normalize($d['isbn']) : null;
        $authors = array_values(array_filter(array_map(
            static fn(string $a): string => trim($a),
            preg_split('/[,;\n]+/', $d['authors']) ?: []
        )));

        $marcRecord = $this->buildMarcRecord([
            'leader'           => '00000nam a2200000 i 4500',
            'isbn_13'          => $isbn13,
            'language'         => $d['language'],
            'main_author'      => $authors[0] ?? '',
            'title'            => $d['title'],
            'edition'          => $d['edition_statement'],
            'publisher'        => $d['publisher'],
            'publication_date' => $d['publication_year'],
            'summary'          => $d['description'],
            'resource_url'     => $d['digital_url'],
            'resource_type'    => $d['resource_type'],
        ]);

        $totalCopies = max(0, (int) $d['total_copies']);

        $stmt = $this->db->prepare(
            'INSERT INTO resources (
                isbn_13, marc_leader, title, authors, marc_record,
                publisher, edition_statement, publication_year,
                category_id, branch_id, support_type, resource_type,
                description, language, cover_image, location, digital_url,
                acquisition_price, replacement_cost, acquisition_date, acquired_at,
                is_new_acquisition, total_copies, available_copies, is_active
            ) VALUES (
                :isbn_13, :marc_leader, :title, :authors, :marc_record,
                :publisher, :edition_statement, :publication_year,
                :category_id, :branch_id, :support_type, :resource_type,
                :description, :language, :cover_image, :location, :digital_url,
                :acquisition_price, :replacement_cost, :acquisition_date, :acquired_at,
                :is_new_acquisition, :total_copies, :available_copies, 1
            )'
        );

        $stmt->execute([
            'isbn_13'           => $isbn13,
            'marc_leader'       => '00000nam a2200000 i 4500',
            'title'             => $d['title'],
            'authors'           => json_encode($authors, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'marc_record'       => json_encode($marcRecord, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'publisher'         => $d['publisher'] !== '' ? $d['publisher'] : null,
            'edition_statement' => $d['edition_statement'] !== '' ? $d['edition_statement'] : null,
            'publication_year'  => $d['publication_year'] !== '' ? (int) $d['publication_year'] : null,
            'category_id'       => (int) $d['category_id'],
            'branch_id'         => (int) $d['branch_id'] > 0 ? (int) $d['branch_id'] : null,
            'support_type'      => $d['support_type'],
            'resource_type'     => $d['resource_type'],
            'description'       => $d['description'] !== '' ? $d['description'] : null,
            'language'          => $d['language'] !== '' ? $d['language'] : 'es',
            'cover_image'       => $d['cover_image'] !== '' ? $d['cover_image'] : null,
            'location'          => $d['location'] !== '' ? $d['location'] : null,
            'digital_url'       => $d['digital_url'] !== '' ? $d['digital_url'] : null,
            'acquisition_price' => $d['acquisition_price'] !== '' ? (float) $d['acquisition_price'] : null,
            'replacement_cost'  => (float) ($d['replacement_cost'] !== '' ? $d['replacement_cost'] : 0),
            'acquisition_date'  => $d['acquisition_date'] !== '' ? $d['acquisition_date'] : null,
            'acquired_at'       => $d['acquisition_date'] !== '' ? $d['acquisition_date'] . ' 00:00:00' : date('Y-m-d H:i:s'),
            'is_new_acquisition'=> (int) $d['is_new_acquisition'],
            'total_copies'      => $totalCopies,
            'available_copies'  => $totalCopies,
        ]);

        return (int) $this->db->lastInsertId();
    }

    private function doUpdateResource(int $id, array $d): void
    {
        $isbn13  = $d['isbn'] !== '' ? Isbn::normalize($d['isbn']) : null;
        $authors = array_values(array_filter(array_map(
            static fn(string $a): string => trim($a),
            preg_split('/[,;\n]+/', $d['authors']) ?: []
        )));

        $marcRecord = $this->buildMarcRecord([
            'leader'           => '00000nam a2200000 i 4500',
            'isbn_13'          => $isbn13,
            'language'         => $d['language'],
            'main_author'      => $authors[0] ?? '',
            'title'            => $d['title'],
            'edition'          => $d['edition_statement'],
            'publisher'        => $d['publisher'],
            'publication_date' => $d['publication_year'],
            'summary'          => $d['description'],
            'resource_url'     => $d['digital_url'],
            'resource_type'    => $d['resource_type'],
        ]);

        $totalCopies     = max(0, (int) $d['total_copies']);
        $availableCopies = min($totalCopies, max(0, (int) $d['available_copies']));

        $stmt = $this->db->prepare(
            'UPDATE resources SET
                isbn_13 = :isbn_13,
                marc_leader = :marc_leader,
                title = :title,
                authors = :authors,
                marc_record = :marc_record,
                publisher = :publisher,
                edition_statement = :edition_statement,
                publication_year = :publication_year,
                category_id = :category_id,
                branch_id = :branch_id,
                support_type = :support_type,
                resource_type = :resource_type,
                description = :description,
                language = :language,
                cover_image = :cover_image,
                location = :location,
                digital_url = :digital_url,
                acquisition_price = :acquisition_price,
                replacement_cost = :replacement_cost,
                acquisition_date = :acquisition_date,
                is_new_acquisition = :is_new_acquisition,
                total_copies = :total_copies,
                available_copies = :available_copies,
                is_active = :is_active,
                updated_at = NOW()
             WHERE id = :id LIMIT 1'
        );

        $stmt->execute([
            'isbn_13'           => $isbn13,
            'marc_leader'       => '00000nam a2200000 i 4500',
            'title'             => $d['title'],
            'authors'           => json_encode($authors, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'marc_record'       => json_encode($marcRecord, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'publisher'         => $d['publisher'] !== '' ? $d['publisher'] : null,
            'edition_statement' => $d['edition_statement'] !== '' ? $d['edition_statement'] : null,
            'publication_year'  => $d['publication_year'] !== '' ? (int) $d['publication_year'] : null,
            'category_id'       => (int) $d['category_id'],
            'branch_id'         => (int) $d['branch_id'] > 0 ? (int) $d['branch_id'] : null,
            'support_type'      => $d['support_type'],
            'resource_type'     => $d['resource_type'],
            'description'       => $d['description'] !== '' ? $d['description'] : null,
            'language'          => $d['language'] !== '' ? $d['language'] : 'es',
            'cover_image'       => $d['cover_image'] !== '' ? $d['cover_image'] : null,
            'location'          => $d['location'] !== '' ? $d['location'] : null,
            'digital_url'       => $d['digital_url'] !== '' ? $d['digital_url'] : null,
            'acquisition_price' => $d['acquisition_price'] !== '' ? (float) $d['acquisition_price'] : null,
            'replacement_cost'  => (float) ($d['replacement_cost'] !== '' ? $d['replacement_cost'] : 0),
            'acquisition_date'  => $d['acquisition_date'] !== '' ? $d['acquisition_date'] : null,
            'is_new_acquisition'=> (int) $d['is_new_acquisition'],
            'total_copies'      => $totalCopies,
            'available_copies'  => $availableCopies,
            'is_active'         => (int) $d['is_active'],
            'id'                => $id,
        ]);
    }

    private function validateCoverImageFile(array $file): ?string
    {
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if ($error !== UPLOAD_ERR_OK) {
            return 'Error al cargar la imagen de portada.';
        }

        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0) {
            return 'La imagen de portada es inválida.';
        }
        if ($size > 5 * 1024 * 1024) {
            return 'La imagen de portada no puede superar 5MB.';
        }

        $tmpName = (string) ($file['tmp_name'] ?? '');
        $mime = '';
        if (is_file($tmpName)) {
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mime = (string) $finfo->file($tmpName);
        }

        $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        if (!in_array($mime, $allowed, true)) {
            return 'Formato de imagen no permitido. Usa JPG, PNG, WEBP o GIF.';
        }

        return null;
    }

    private function validateDigitalPdfFile(array $file): ?string
    {
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error === UPLOAD_ERR_NO_FILE) {
            return 'Debes cargar un archivo PDF para el libro digital.';
        }
        if ($error !== UPLOAD_ERR_OK) {
            return 'Error al cargar el archivo PDF.';
        }

        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0) {
            return 'El archivo PDF es inválido.';
        }
        if ($size > 50 * 1024 * 1024) {
            return 'El PDF no puede superar 50MB.';
        }

        $tmpName = (string) ($file['tmp_name'] ?? '');
        $mime = '';
        if (is_file($tmpName)) {
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mime = (string) $finfo->file($tmpName);
        }

        $extension = strtolower((string) pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        if ($mime !== 'application/pdf' || $extension !== 'pdf') {
            return 'Solo se permiten archivos PDF válidos.';
        }

        return null;
    }

    public function deactivate(Request $request, string $id = ''): Response
    {
        $authUser = $this->resolveAuthUser();
        if ($authUser === null) {
            Session::destroy();
            return Response::redirect(BASE_URL . '/login');
        }

        if (!hash_equals(Session::get('_csrf_token', ''), $request->post('_csrf_token', ''))) {
            Session::flash('error', 'Token de seguridad inválido.');
            return Response::redirect(BASE_URL . '/admin/resources');
        }

        $resourceId = (int) $id;
        if ($resourceId <= 0) {
            Session::flash('error', 'Recurso inválido.');
            return Response::redirect(BASE_URL . '/admin/resources');
        }

        $stmt = $this->db->prepare('SELECT id, title FROM resources WHERE id = ? LIMIT 1');
        $stmt->execute([$resourceId]);
        $resource = $stmt->fetch();

        if (!$resource) {
            Session::flash('error', 'No se encontró el recurso.');
            return Response::redirect(BASE_URL . '/admin/resources');
        }

        $this->db->prepare('UPDATE resources SET is_active = 0, updated_at = NOW() WHERE id = ?')->execute([$resourceId]);

        $this->db->prepare(
            "INSERT INTO audit_logs (user_id, action, entity_type, entity_id, details, created_at)
             VALUES (?, 'resource.deactivate', 'resource', ?, ?, NOW())"
        )->execute([$authUser['id'], $resourceId, json_encode(['title' => $resource['title']])]);

        Session::flash('success', 'Recurso desactivado correctamente.');
        return Response::redirect(BASE_URL . '/admin/resources');
    }

    public function reactivate(Request $request, string $id = ''): Response
    {
        $authUser = $this->resolveAuthUser();
        if ($authUser === null) {
            Session::destroy();
            return Response::redirect(BASE_URL . '/login');
        }

        if (!hash_equals(Session::get('_csrf_token', ''), $request->post('_csrf_token', ''))) {
            Session::flash('error', 'Token de seguridad inválido.');
            return Response::redirect(BASE_URL . '/admin/resources');
        }

        $resourceId = (int) $id;
        if ($resourceId <= 0) {
            Session::flash('error', 'Recurso inválido.');
            return Response::redirect(BASE_URL . '/admin/resources');
        }

        $stmt = $this->db->prepare('SELECT id, title FROM resources WHERE id = ? LIMIT 1');
        $stmt->execute([$resourceId]);
        $resource = $stmt->fetch();

        if (!$resource) {
            Session::flash('error', 'No se encontró el recurso.');
            return Response::redirect(BASE_URL . '/admin/resources');
        }

        $this->db->prepare('UPDATE resources SET is_active = 1, updated_at = NOW() WHERE id = ?')->execute([$resourceId]);

        $this->db->prepare(
            "INSERT INTO audit_logs (user_id, action, entity_type, entity_id, details, created_at)
             VALUES (?, 'resource.reactivate', 'resource', ?, ?, NOW())"
        )->execute([$authUser['id'], $resourceId, json_encode(['title' => $resource['title']])]);

        Session::flash('success', 'Recurso reactivado correctamente.');
        return Response::redirect(BASE_URL . '/admin/resources');
    }

    private function storeCoverImage(array $file): ?string
    {
        $tmpName = (string) ($file['tmp_name'] ?? '');
        if (!is_uploaded_file($tmpName)) {
            return null;
        }

        $mime = (string) (new \finfo(FILEINFO_MIME_TYPE))->file($tmpName);
        $ext = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            'image/gif'  => 'gif',
            default      => null,
        };

        if ($ext === null) {
            return null;
        }

        $directory = BASE_PATH . '/public/uploads/resources';
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            return null;
        }

        try {
            $entropy = bin2hex(random_bytes(4));
        } catch (\Throwable $e) {
            $entropy = (string) mt_rand(100000, 999999);
        }

        $filename    = 'resource_' . date('Ymd_His') . '_' . $entropy . '.' . $ext;
        $destination = $directory . '/' . $filename;

        if (!move_uploaded_file($tmpName, $destination)) {
            return null;
        }

        return '/uploads/resources/' . $filename;
    }

    private function storeDigitalPdf(array $file): ?string
    {
        $tmpName = (string) ($file['tmp_name'] ?? '');
        if (!is_uploaded_file($tmpName)) {
            return null;
        }

        $directory = BASE_PATH . '/storage/uploads/digital-resources';
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            return null;
        }

        try {
            $entropy = bin2hex(random_bytes(8));
        } catch (\Throwable) {
            $entropy = (string) mt_rand(10000000, 99999999);
        }

        $filename = 'ebook_' . date('Ymd_His') . '_' . $entropy . '.pdf';
        $destination = $directory . '/' . $filename;

        if (!move_uploaded_file($tmpName, $destination)) {
            return null;
        }

        return '/storage/uploads/digital-resources/' . $filename;
    }

    /** Removes a stored cover image file when it belongs to /public/uploads/resources. */
    private function deleteStoredCoverImage(string $coverImagePath): void
    {
        $relative = trim($coverImagePath);
        if ($relative === '' || !str_starts_with($relative, '/uploads/resources/')) {
            return;
        }

        $uploadsRoot = realpath(BASE_PATH . '/public/uploads/resources');
        if ($uploadsRoot === false) {
            return;
        }

        $absolute = realpath(BASE_PATH . '/public' . $relative);
        if ($absolute === false || !is_file($absolute)) {
            return;
        }

        if (!str_starts_with($absolute, $uploadsRoot . DIRECTORY_SEPARATOR)) {
            return;
        }

        @unlink($absolute);
    }

    private function deleteStoredDigitalPdf(string $pdfPath): void
    {
        $relative = trim($pdfPath);
        if ($relative === '' || !str_starts_with($relative, '/storage/uploads/digital-resources/')) {
            return;
        }

        $storageRoot = realpath(BASE_PATH . '/storage/uploads/digital-resources');
        if ($storageRoot === false) {
            return;
        }

        $absolute = realpath(BASE_PATH . $relative);
        if ($absolute === false || !is_file($absolute)) {
            return;
        }

        if (!str_starts_with($absolute, $storageRoot . DIRECTORY_SEPARATOR)) {
            return;
        }

        @unlink($absolute);
    }
}
