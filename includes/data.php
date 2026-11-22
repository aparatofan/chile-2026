<?php
/**
 * Trip data: stages, itinerary, checklist, apps and practical info.
 *
 * This file lives in a PUBLIC repository, so it holds no private data:
 * no passport or ticket numbers, no addresses, phone numbers or hosts' names.
 * Those come from Chile 2026 → Ustawienia (WordPress options) and are only
 * sent to logged-in travellers. Placeholders such as {gospodarze_santiago}
 * are filled in from those settings.
 *
 * Event fields:
 *   id, stage, date (Y-m-d|null), time (H:i|null), end_date, end_time,
 *   type (flight|bus|stay|activity|car|transfer|info|task|free),
 *   title, title_public (shown to visitors when title has a placeholder),
 *   place, detail, note (private default), status (booked|todo|''),
 *   booking (true = belongs to the "Do zarezerwowania" list), link,
 *   important (true = "Ważne" chip). type task = a to-do in the plan that can be ticked off.
 *
 * @package MM_Chile_2026
 */

defined( 'ABSPATH' ) || exit;

/** Trip frame. Times are local to the place they happen. */
function mmcl_trip() {
	return array(
		'title'       => 'Chile 2026/27',
		'start'       => '2026-12-20T12:50:00+01:00', // WAW departure.
		'end'         => '2027-01-17T18:15:00+01:00', // WAW arrival.
		'first_day'   => '2026-12-20',
		'last_day'    => '2027-01-17',
		'chile_tz'    => 'America/Santiago',
	);
}

/** Stages in travel order. Colours match CSS tokens. */
function mmcl_stages() {
	return array(
		array( 'id' => 'wylot',     'name' => 'Wylot',                   'stamp' => 'Salida',     'from' => '2026-12-20', 'to' => '2026-12-20', 'color' => 'ink' ),
		array( 'id' => 'santiago',  'name' => 'Santiago',                'stamp' => 'Santiago',   'from' => '2026-12-21', 'to' => '2026-12-23', 'color' => 'copihue' ),
		array( 'id' => 'pucon',     'name' => 'Pucón',                   'stamp' => 'Araucanía',  'from' => '2026-12-24', 'to' => '2026-12-29', 'color' => 'araucaria' ),
		array( 'id' => 'patagonia', 'name' => 'Patagonia',               'stamp' => 'Patagonia',  'from' => '2026-12-30', 'to' => '2027-01-04', 'color' => 'glaciar' ),
		array( 'id' => 'atacama',   'name' => 'Atacama',                 'stamp' => 'Atacama',    'from' => '2027-01-05', 'to' => '2027-01-11', 'color' => 'terracota' ),
		array( 'id' => 'ocean',     'name' => 'Viña del Mar i Valparaíso', 'stamp' => 'Pacífico', 'from' => '2027-01-12', 'to' => '2027-01-15', 'color' => 'pacifico' ),
		array( 'id' => 'powrot',    'name' => 'Powrót',                  'stamp' => 'Regreso',    'from' => '2027-01-16', 'to' => '2027-01-17', 'color' => 'ink' ),
	);
}

/** Places on the north–south route ribbon (latitude in degrees, south negative). */
function mmcl_places() {
	return array(
		array( 'id' => 'spa',      'name' => 'San Pedro de Atacama', 'lat' => -22.91, 'stage' => 'atacama' ),
		array( 'id' => 'vina',     'name' => 'Viña del Mar',         'lat' => -33.02, 'stage' => 'ocean' ),
		array( 'id' => 'santiago', 'name' => 'Santiago',             'lat' => -33.45, 'stage' => 'santiago' ),
		array( 'id' => 'pucon',    'name' => 'Pucón',                'lat' => -39.27, 'stage' => 'pucon' ),
		array( 'id' => 'pmontt',   'name' => 'Puerto Montt',         'lat' => -41.47, 'stage' => 'pucon' ),
		array( 'id' => 'natales',  'name' => 'Puerto Natales',       'lat' => -51.73, 'stage' => 'patagonia' ),
	);
}

/** Legs in travel order: mode is flight or ground. */
function mmcl_legs() {
	return array(
		array( 'from' => 'santiago', 'to' => 'pucon',    'mode' => 'ground', 'date' => '2026-12-23' ),
		array( 'from' => 'pucon',    'to' => 'pmontt',   'mode' => 'ground', 'date' => '2026-12-29' ),
		array( 'from' => 'pmontt',   'to' => 'natales',  'mode' => 'flight', 'date' => '2026-12-30' ),
		array( 'from' => 'natales',  'to' => 'santiago', 'mode' => 'flight', 'date' => '2027-01-04' ),
		array( 'from' => 'santiago', 'to' => 'spa',      'mode' => 'flight', 'date' => '2027-01-05' ),
		array( 'from' => 'spa',      'to' => 'santiago', 'mode' => 'flight', 'date' => '2027-01-11' ),
		array( 'from' => 'santiago', 'to' => 'vina',     'mode' => 'ground', 'date' => '2027-01-12' ),
		array( 'from' => 'vina',     'to' => 'santiago', 'mode' => 'ground', 'date' => '2027-01-16' ),
	);
}

/** The itinerary. */
function mmcl_events() {
	return array(
		// ——— Wylot ———
		array(
			'id' => 'lot-tam', 'stage' => 'wylot', 'type' => 'flight',
			'date' => '2026-12-20', 'time' => '12:50', 'end_date' => '2026-12-21', 'end_time' => '10:00',
			'title' => 'Warszawa → Santiago', 'place' => 'WAW → SCL, Turkish Airlines',
			'detail' => '25 h 10 min, 1 przesiadka', 'status' => 'booked', 'booking' => true,
			'note' => 'Kupione przez eSky. Rezerwacja „w realizacji” — czekamy na bilety elektroniczne.',
		),

		// ——— Santiago ———
		array(
			'id' => 'nocleg-santiago', 'stage' => 'santiago', 'type' => 'stay',
			'date' => '2026-12-21', 'time' => null, 'end_date' => '2026-12-23', 'end_time' => null,
			'title' => 'Nocleg: {gospodarze_santiago}', 'title_public' => 'Nocleg u przyjaciół',
			'place' => 'Santiago', 'detail' => '', 'status' => 'booked', 'booking' => false,
		),
		array(
			'id' => 'betel', 'stage' => 'santiago', 'type' => 'task',
			'date' => '2026-12-22', 'time' => null,
			'title' => 'Wizyta w Betel', 'place' => '', 'detail' => '',
			'status' => '', 'booking' => false,
		),
		array(
			'id' => 'jac', 'stage' => 'santiago', 'type' => 'bus',
			'date' => '2026-12-23', 'time' => '22:20', 'end_date' => '2026-12-24', 'end_time' => null,
			'title' => 'Nocny autobus do Pucón', 'place' => 'Terminal Sur → Pucón Terminal JAC',
			'detail' => 'JAC, Salón Cama', 'status' => 'booked', 'booking' => true,
			'note' => 'Miejsca 1 i 2. Bilet w telefonie lub wydrukowany + paszport. Być 20 min przed odjazdem.',
		),

		// ——— Pucón ———
		array(
			'id' => 'auto-pucon', 'stage' => 'pucon', 'type' => 'car',
			'date' => '2026-12-24', 'time' => null, 'end_date' => '2026-12-29', 'end_time' => null,
			'title' => 'Wynajem auta w Pucón', 'place' => 'Odbiór rano po autobusie, zwrot przed autobusem do Puerto Montt',
			'detail' => 'Lake Lodge jest na górze — bez auta ani rusz', 'status' => 'todo', 'booking' => true,
		),
		array(
			'id' => 'lake-lodge', 'stage' => 'pucon', 'type' => 'stay',
			'date' => '2026-12-24', 'time' => null, 'end_date' => '2026-12-29', 'end_time' => null,
			'title' => 'Lake Lodge', 'place' => 'Pucón', 'detail' => 'Pokój dwuosobowy Deluxe, 5 nocy',
			'status' => 'booked', 'booking' => true,
		),
		array(
			'id' => 'cuevas', 'stage' => 'pucon', 'type' => 'activity',
			'date' => '2026-12-24', 'time' => null,
			'title' => 'Cuevas Volcánicas de Villarrica', 'place' => 'Wulkan Villarrica',
			'detail' => 'Jaskinie wulkaniczne. Spokojny dzień przyjazdu.', 'status' => 'todo', 'booking' => true,
		),
		array(
			'id' => 'zakupy-2412', 'stage' => 'pucon', 'type' => 'task',
			'date' => '2026-12-24', 'time' => null,
			'title' => 'Zrobić zakupy', 'place' => '', 'detail' => '25 grudnia sklepy są zamknięte.',
			'status' => '', 'booking' => false,
			'important' => true,
		),
		array(
			'id' => 'cani', 'stage' => 'pucon', 'type' => 'activity',
			'date' => '2026-12-25', 'time' => null,
			'title' => 'Santuario El Cañi', 'place' => 'Mirador Melidekiñ',
			'detail' => 'Całodniowy trekking. Boże Narodzenie — sprawdzić, czy jest otwarte.', 'status' => 'todo', 'booking' => true,
		),
		array(
			'id' => 'huerquehue', 'stage' => 'pucon', 'type' => 'activity',
			'date' => '2026-12-26', 'time' => null,
			'title' => 'Park Narodowy Huerquehue', 'place' => 'Sendero Los Lagos',
			'detail' => 'Górskie jeziora i lasy araukarii. Bilety CONAF online.', 'status' => 'todo', 'booking' => true,
			'link' => 'https://www.pasesparques.cl',
		),
		array(
			'id' => 'termas', 'stage' => 'pucon', 'type' => 'activity',
			'date' => '2026-12-27', 'time' => null,
			'title' => 'Termas Martini', 'place' => 'Dawniej Termas Geométricas, ok. 2 h od Pucón',
			'detail' => 'Gorące źródła w lesie. Rezerwacja z wyprzedzeniem.', 'status' => 'todo', 'booking' => true,
		),
		array(
			'id' => 'przymiarka', 'stage' => 'pucon', 'type' => 'task',
			'date' => '2026-12-27', 'time' => null,
			'title' => 'Przymiarka ubrań do wejścia na wulkan', 'place' => '', 'detail' => 'Jutro wulkan Villarrica.',
			'status' => '', 'booking' => false,
			'important' => true,
		),
		array(
			'id' => 'wulkan', 'stage' => 'pucon', 'type' => 'activity',
			'date' => '2026-12-28', 'time' => null,
			'title' => 'Wulkan Villarrica z przewodnikiem', 'place' => 'Plan A',
			'detail' => 'Wejście tylko przy zielonym alercie Sernageomin. Plan B: wycieczka na e-bike’ach.', 'status' => 'todo', 'booking' => true,
			'link' => 'https://rnvv.sernageomin.cl',
		),
		array(
			'id' => 'bus-pmontt', 'stage' => 'pucon', 'type' => 'bus',
			'date' => '2026-12-29', 'time' => null,
			'title' => 'Autobus Pucón → Puerto Montt', 'place' => '', 'detail' => '',
			'status' => 'booked', 'booking' => true,
			'note' => 'Na liście Ani: zarezerwowany. Sprawdzić, czy opłacony, i godzinę odjazdu.',
		),
		array(
			'id' => 'nocleg-pmontt', 'stage' => 'pucon', 'type' => 'stay',
			'date' => '2026-12-29', 'time' => null, 'end_date' => '2026-12-30', 'end_time' => null,
			'title' => 'Nocleg w Puerto Montt', 'place' => 'Puerto Montt', 'detail' => '',
			'status' => 'booked', 'booking' => true,
		),
		array(
			'id' => 'odprawa-pmc', 'stage' => 'pucon', 'type' => 'task',
			'date' => '2026-12-29', 'time' => null,
			'title' => 'Zrobić odprawę przed wylotem', 'place' => '', 'detail' => 'Lot Puerto Montt → Puerto Natales, 30 grudnia.',
			'status' => '', 'booking' => false,
			'important' => true,
		),

		// ——— Patagonia ———
		array(
			'id' => 'lot-pmc-pnt', 'stage' => 'patagonia', 'type' => 'flight',
			'date' => '2026-12-30', 'time' => null,
			'title' => 'Puerto Montt → Puerto Natales', 'place' => 'PMC → PNT', 'detail' => '',
			'status' => 'todo', 'booking' => true,
		),
		array(
			'id' => 'nocleg-natales', 'stage' => 'patagonia', 'type' => 'stay',
			'date' => '2026-12-30', 'time' => null, 'end_date' => '2027-01-04', 'end_time' => null,
			'title' => 'Nocleg w Puerto Natales', 'place' => 'Puerto Natales', 'detail' => '5 nocy',
			'status' => 'todo', 'booking' => true,
		),
		array(
			'id' => 'zakupy-3012', 'stage' => 'patagonia', 'type' => 'task',
			'date' => '2026-12-30', 'time' => null,
			'title' => 'Zrobić zakupy', 'place' => '', 'detail' => 'Na Sylwestra i Nowy Rok — 1 stycznia sklepy są zamknięte.',
			'status' => '', 'booking' => false,
			'important' => true,
		),
		array(
			'id' => 'tdp-bilety', 'stage' => 'patagonia', 'type' => 'info',
			'date' => '2026-12-31', 'time' => null,
			'title' => 'Bilety wstępu do Torres del Paine', 'place' => 'Pases Parques',
			'detail' => 'Wstęp to osobny bilet niż wycieczki.', 'status' => 'todo', 'booking' => true,
			'link' => 'https://www.pasesparques.cl',
		),
		array(
			'id' => 'base-torres', 'stage' => 'patagonia', 'type' => 'activity',
			'date' => '2026-12-31', 'time' => null,
			'title' => 'Base de las Torres', 'place' => 'Mirador Las Torres, Torres del Paine',
			'detail' => 'Ok. 20 km w obie strony. Na tym szlaku wymagany autoryzowany przewodnik.', 'status' => 'todo', 'booking' => true,
		),
		array(
			'id' => 'sylwester', 'stage' => 'patagonia', 'type' => 'info',
			'date' => '2026-12-31', 'time' => null,
			'title' => 'Sylwester w Puerto Natales', 'place' => '', 'detail' => '',
			'status' => '', 'booking' => false,
		),
		array(
			'id' => 'tdp-minivan', 'stage' => 'patagonia', 'type' => 'activity',
			'date' => '2027-01-01', 'time' => null,
			'title' => 'Torres del Paine — objazd minivanem', 'place' => 'Wycieczka zorganizowana', 'detail' => '',
			'status' => 'todo', 'booking' => true,
		),
		array(
			'id' => 'wolne-0201', 'stage' => 'patagonia', 'type' => 'free',
			'date' => '2027-01-02', 'time' => null,
			'title' => 'Dzień wolny', 'place' => '', 'detail' => '', 'status' => '', 'booking' => false,
		),
		array(
			'id' => 'grey', 'stage' => 'patagonia', 'type' => 'activity',
			'date' => '2027-01-03', 'time' => null,
			'title' => 'Rejs do lodowca Grey', 'place' => 'Lago Grey', 'detail' => '',
			'status' => 'todo', 'booking' => true,
		),
		array(
			'id' => 'odprawa-pnt', 'stage' => 'patagonia', 'type' => 'task',
			'date' => '2027-01-03', 'time' => null,
			'title' => 'Zrobić odprawę przed wylotem', 'place' => '', 'detail' => 'Lot Puerto Natales → Santiago, 4 stycznia.',
			'status' => '', 'booking' => false,
			'important' => true,
		),
		array(
			'id' => 'lot-pnt-scl', 'stage' => 'patagonia', 'type' => 'flight',
			'date' => '2027-01-04', 'time' => null,
			'title' => 'Puerto Natales → Santiago', 'place' => 'PNT → SCL', 'detail' => '',
			'status' => 'todo', 'booking' => true,
		),
		array(
			'id' => 'nocleg-0401', 'stage' => 'patagonia', 'type' => 'stay',
			'date' => '2027-01-04', 'time' => null, 'end_date' => '2027-01-05', 'end_time' => null,
			'title' => 'Nocleg w Santiago', 'place' => 'Santiago', 'detail' => 'Do ustalenia',
			'status' => 'todo', 'booking' => true,
		),
		array(
			'id' => 'odprawa-scl-cjc', 'stage' => 'patagonia', 'type' => 'task',
			'date' => '2027-01-04', 'time' => null,
			'title' => 'Zrobić odprawę przed wylotem', 'place' => '', 'detail' => 'Lot Santiago → Calama, 5 stycznia.',
			'status' => '', 'booking' => false,
			'important' => true,
		),

		// ——— Atacama ———
		array(
			'id' => 'lot-scl-cjc', 'stage' => 'atacama', 'type' => 'flight',
			'date' => '2027-01-05', 'time' => null,
			'title' => 'Santiago → Calama', 'place' => 'SCL → CJC', 'detail' => '',
			'status' => 'todo', 'booking' => true,
		),
		array(
			'id' => 'transfer-tam', 'stage' => 'atacama', 'type' => 'transfer',
			'date' => '2027-01-05', 'time' => null,
			'title' => 'Transfer Calama → San Pedro', 'place' => 'Ok. 1,5 h',
			'detail' => 'Dopasować do godziny przylotu. Polecana agencja: Transfer Altiplánico.', 'status' => 'todo', 'booking' => true,
			'link' => 'https://transferaltiplanico.cl/en/#cotizar',
		),
		array(
			'id' => 'nocleg-spa', 'stage' => 'atacama', 'type' => 'stay',
			'date' => '2027-01-05', 'time' => null, 'end_date' => '2027-01-11', 'end_time' => null,
			'title' => 'Nocleg: {gospodarze_sanpedro}', 'title_public' => 'Nocleg u znajomych',
			'place' => 'San Pedro de Atacama', 'detail' => '6 nocy', 'status' => 'booked', 'booking' => true,
		),
		array(
			'id' => 'atac-luna', 'stage' => 'atacama', 'type' => 'activity', 'date' => null, 'time' => null,
			'title' => 'Valle de la Luna i Valle del Arcoíris', 'place' => 'Dolina Księżycowa i Tęczowa Dolina',
			'detail' => 'Przewodnik: {przewodnik}', 'status' => 'booked', 'booking' => true,
		),
		array(
			'id' => 'atac-lagunas', 'stage' => 'atacama', 'type' => 'activity', 'date' => null, 'time' => null,
			'title' => 'Piedras Rojas, laguny altiplano i Salar de Atacama', 'place' => 'Laguna Chaxa',
			'detail' => 'Przewodnik: {przewodnik}', 'status' => 'booked', 'booking' => true,
		),
		array(
			'id' => 'atac-tatio', 'stage' => 'atacama', 'type' => 'activity', 'date' => null, 'time' => null,
			'title' => 'Gejzery El Tatio i Laguna Cejar', 'place' => 'Wyjazd przed świtem, ok. 4300 m',
			'detail' => 'Przewodnik: {przewodnik}', 'status' => 'booked', 'booking' => true,
		),
		array(
			'id' => 'atac-toco', 'stage' => 'atacama', 'type' => 'activity', 'date' => null, 'time' => null,
			'title' => 'Cerro Toco', 'place' => 'Ok. 5600 m — najlepiej pod koniec pobytu',
			'detail' => 'Przewodnik: {przewodnik}', 'status' => 'booked', 'booking' => true,
		),
		array(
			'id' => 'atac-salares', 'stage' => 'atacama', 'type' => 'activity', 'date' => null, 'time' => null,
			'title' => 'Ruta de los Salares', 'place' => '',
			'detail' => 'Przewodnik: {przewodnik}', 'status' => 'booked', 'booking' => true,
		),
		array(
			'id' => 'stargazing', 'stage' => 'atacama', 'type' => 'activity',
			'date' => '2027-01-07', 'time' => null,
			'title' => 'Obserwacja nieba', 'place' => 'San Pedro de Atacama',
			'detail' => 'Nów księżyca wypada 8 stycznia — niebo będzie wyjątkowo ciemne.', 'status' => 'booked', 'booking' => true,
		),
		array(
			'id' => 'odprawa-cjc', 'stage' => 'atacama', 'type' => 'task',
			'date' => '2027-01-10', 'time' => null,
			'title' => 'Zrobić odprawę przed wylotem', 'place' => '', 'detail' => 'Lot Calama → Santiago, 11 stycznia.',
			'status' => '', 'booking' => false,
			'important' => true,
		),
		array(
			'id' => 'transfer-powrot', 'stage' => 'atacama', 'type' => 'transfer',
			'date' => '2027-01-11', 'time' => null,
			'title' => 'Transfer San Pedro → Calama', 'place' => 'Ok. 1,5 h',
			'detail' => 'Dopasować do godziny wylotu. Polecana agencja: Transfer Altiplánico.', 'status' => 'todo', 'booking' => true,
			'link' => 'https://transferaltiplanico.cl/en/#cotizar',
		),
		array(
			'id' => 'lot-cjc-scl', 'stage' => 'atacama', 'type' => 'flight',
			'date' => '2027-01-11', 'time' => null,
			'title' => 'Calama → Santiago', 'place' => 'CJC → SCL', 'detail' => '',
			'status' => 'todo', 'booking' => true,
		),
		array(
			'id' => 'nocleg-1101', 'stage' => 'atacama', 'type' => 'stay',
			'date' => '2027-01-11', 'time' => null, 'end_date' => '2027-01-12', 'end_time' => null,
			'title' => 'Nocleg w Santiago', 'place' => 'Santiago', 'detail' => 'Do ustalenia',
			'status' => 'todo', 'booking' => true,
		),

		// ——— Ocean ———
		array(
			'id' => 'nocleg-vina', 'stage' => 'ocean', 'type' => 'stay',
			'date' => '2027-01-12', 'time' => null, 'end_date' => '2027-01-15', 'end_time' => null,
			'title' => 'Nocleg w Viña del Mar', 'place' => 'Viña del Mar', 'detail' => '3 noce',
			'status' => 'todo', 'booking' => true,
		),
		array(
			'id' => 'odprawa-waw', 'stage' => 'ocean', 'type' => 'task',
			'date' => '2027-01-15', 'time' => null,
			'title' => 'Odprawa przed wylotem do Warszawy', 'place' => '', 'detail' => 'Turkish Airlines, SCL → WAW, 16 stycznia o 11:45.',
			'status' => '', 'booking' => false,
			'important' => true,
		),
		array(
			'id' => 'valpo', 'stage' => 'ocean', 'type' => 'activity', 'date' => null, 'time' => null,
			'title' => 'Dzień w Valparaíso', 'place' => 'Kolorowe wzgórza i windy ascensores',
			'detail' => '', 'status' => '', 'booking' => false,
		),
		array(
			'id' => 'nocleg-1501', 'stage' => 'ocean', 'type' => 'stay',
			'date' => '2027-01-15', 'time' => null, 'end_date' => '2027-01-16', 'end_time' => null,
			'title' => 'Ostatni nocleg przed wylotem', 'place' => '', 'detail' => 'Do ustalenia — najlepiej blisko lotniska SCL',
			'status' => 'todo', 'booking' => true,
		),

		// ——— Powrót ———
		array(
			'id' => 'dojazd-scl', 'stage' => 'powrot', 'type' => 'transfer',
			'date' => '2027-01-16', 'time' => '08:45',
			'title' => 'Na lotnisko SCL', 'place' => 'Najpóźniej 3 h przed wylotem', 'detail' => '',
			'status' => 'todo', 'booking' => true,
		),
		array(
			'id' => 'lot-powrot', 'stage' => 'powrot', 'type' => 'flight',
			'date' => '2027-01-16', 'time' => '11:45', 'end_date' => '2027-01-17', 'end_time' => '18:15',
			'title' => 'Santiago → Warszawa', 'place' => 'SCL → WAW, Turkish Airlines',
			'detail' => '26 h 30 min, 1 przesiadka', 'status' => 'booked', 'booking' => true,
		),
	);
}

/** Checklist categories (tabs). */
function mmcl_checklist_cats() {
	return array(
		array( 'id' => 'przed',    'name' => 'Przed wyjazdem', 'stamp' => 'Antes' ),
		array( 'id' => 'wjazd',    'name' => 'Wjazd do Chile', 'stamp' => 'Aduana' ),
		array( 'id' => 'pucon',    'name' => 'Pucón i auto',   'stamp' => 'Araucanía' ),
		array( 'id' => 'patagonia','name' => 'Patagonia',      'stamp' => 'Patagonia' ),
		array( 'id' => 'atacama',  'name' => 'Atacama',        'stamp' => 'Atacama' ),
		array( 'id' => 'ocean',    'name' => 'Wybrzeże',       'stamp' => 'Pacífico' ),
		array( 'id' => 'pakowanie','name' => 'Pakowanie',      'stamp' => 'Equipaje' ),
	);
}

/**
 * Checklist items. scope: shared (one tick for both) or personal (each traveller ticks their own).
 * hint is optional; link optional.
 */
function mmcl_checklist_items() {
	$s = 'shared';
	$p = 'personal';
	return array(
		// Przed wyjazdem.
		array( 'id' => 'paszport',      'cat' => 'przed', 'scope' => $p, 'label' => 'Paszport ważny przez cały pobyt', 'hint' => 'Najlepiej z zapasem 6 miesięcy.' ),
		array( 'id' => 'paszport-kopia','cat' => 'przed', 'scope' => $p, 'label' => 'Skan paszportu w telefonie i w chmurze, kopia papierowa osobno' ),
		array( 'id' => 'ubezpieczenie', 'cat' => 'przed', 'scope' => $p, 'label' => 'Ubezpieczenie z kosztami leczenia i ratownictwem w górach', 'hint' => 'Sprawdzić limit wysokości — Cerro Toco ma ok. 5600 m, a wiele polis kończy się na 3000–4000 m.' ),
		array( 'id' => 'karty',         'cat' => 'przed', 'scope' => $p, 'label' => 'Dwie karty płatnicze, trzymane osobno', 'hint' => 'Jedna z niskimi opłatami za przewalutowanie (Revolut, Wise).' ),
		array( 'id' => 'prawo-jazdy',   'cat' => 'przed', 'scope' => $p, 'label' => 'Prawo jazdy i międzynarodowe prawo jazdy', 'hint' => 'Dla kierowcy w Pucón. Zapytać wypożyczalnię, czego wymaga.' ),
		array( 'id' => 'leki',          'cat' => 'przed', 'scope' => $p, 'label' => 'Leki stałe na cały pobyt + recepty' ),
		array( 'id' => 'esky',          'cat' => 'przed', 'scope' => $s, 'label' => 'Odebrać bilety elektroniczne od eSky', 'hint' => 'Rezerwacja Turkish Airlines była jeszcze „w realizacji”.' ),
		array( 'id' => 'odprawa-tam',   'cat' => 'przed', 'scope' => $s, 'label' => 'Odprawa online Turkish Airlines (19.12)', 'hint' => 'Otwiera się 24 h przed wylotem.' ),
		array( 'id' => 'bagaz-krajowy', 'cat' => 'przed', 'scope' => $s, 'label' => 'Sprawdzić bagaż w lotach krajowych przed zakupem', 'hint' => 'Turkish daje 2 × 23 kg na osobę, a krajowe taryfy często tylko bagaż podręczny. Spakować się pod najbardziej restrykcyjny lot.' ),
		array( 'id' => 'przylot',       'cat' => 'przed', 'scope' => $s, 'label' => 'Ustalić z gospodarzami w Santiago przylot 21.12 o 10:00', 'hint' => 'Gospodarze: {gospodarze_santiago}. Kto odbiera i jak dojechać z lotniska.' ),
		array( 'id' => 'upominki',      'cat' => 'przed', 'scope' => $s, 'label' => 'Upominki dla gospodarzy w Santiago i San Pedro' ),
		array( 'id' => 'mapy',          'cat' => 'przed', 'scope' => $s, 'label' => 'Mapy offline całej trasy', 'hint' => 'W Patagonii i na altiplano zasięg bywa żaden.' ),
		array( 'id' => 'esim',          'cat' => 'przed', 'scope' => $s, 'label' => 'eSIM lub chilijska karta SIM (Entel, Movistar, Claro)' ),
		array( 'id' => 'hiszpanski',    'cat' => 'przed', 'scope' => $s, 'label' => 'Tłumacz z hiszpańskim offline w telefonie' ),
		array( 'id' => 'kopie-rezerwacji','cat' => 'przed', 'scope' => $s, 'label' => 'Wszystkie rezerwacje i bilety w telefonie offline' ),

		// Wjazd do Chile.
		array( 'id' => 'sag',           'cat' => 'wjazd', 'scope' => $p, 'label' => 'Deklaracja SAG/Aduana wypełniona', 'hint' => 'Online na ingresoachile.cl albo na papierze w samolocie.', 'link' => 'https://www.ingresoachile.cl' ),
		array( 'id' => 'jedzenie',      'cat' => 'wjazd', 'scope' => $s, 'label' => 'Bez świeżych owoców, wędlin, serów, miodu i nasion w bagażu', 'hint' => 'Albo je zadeklarować. Za niezgłoszone produkty grożą kary.' ),
		array( 'id' => 'pdi',           'cat' => 'wjazd', 'scope' => $p, 'label' => 'Zachować dokument wjazdu PDI', 'hint' => 'Zagraniczni turyści płacący w USD mogą w zarejestrowanych hotelach nie płacić 19% IVA — hotel może o niego poprosić.' ),
		array( 'id' => 'adres-wjazd',   'cat' => 'wjazd', 'scope' => $s, 'label' => 'Adres pierwszego noclegu pod ręką', 'hint' => 'Może być potrzebny przy kontroli.' ),

		// Pucón i auto.
		array( 'id' => 'auto-preswit',  'cat' => 'pucon', 'scope' => $s, 'label' => 'Auto z wyższym prześwitem — dojazd do Lake Lodge pod górę', 'hint' => 'Zapytać lodge o stan drogi.' ),
		array( 'id' => 'lodge-wczesnie','cat' => 'pucon', 'scope' => $s, 'label' => 'Zapytać Lake Lodge o wczesne zameldowanie 24.12', 'hint' => 'Autobus przyjeżdża rano — przynajmniej przechowanie bagażu.' ),
		array( 'id' => 'zakupy-wigilia','cat' => 'pucon', 'scope' => $s, 'label' => 'Zakupy spożywcze 24.12', 'hint' => '25 grudnia supermarkety i centra handlowe są zamknięte z mocy prawa.' ),
		array( 'id' => 'cani-otwarte',  'cat' => 'pucon', 'scope' => $s, 'label' => 'Potwierdzić, że El Cañi działa 25.12' ),
		array( 'id' => 'wulkan-alert',  'cat' => 'pucon', 'scope' => $s, 'label' => 'Sprawdzić alert wulkanu Villarrica przed 28.12', 'hint' => 'Wejścia są tylko przy zielonym alercie. We wrześniu 2026 podniesiono go na żółty — plan B może być potrzebny.', 'link' => 'https://rnvv.sernageomin.cl' ),
		array( 'id' => 'termy-rzeczy',  'cat' => 'pucon', 'scope' => $s, 'label' => 'Na termy: stroje, ręczniki, klapki, przekąski' ),
		array( 'id' => 'zwrot-auta',    'cat' => 'pucon', 'scope' => $s, 'label' => 'Zatankować i oddać auto przed autobusem 29.12' ),

		// Patagonia.
		array( 'id' => 'sylwester-kolacja','cat' => 'patagonia', 'scope' => $s, 'label' => 'Kolacja sylwestrowa w Puerto Natales — rezerwacja', 'hint' => 'Po całym dniu na szlaku do Base de las Torres.' ),
		array( 'id' => 'zakupy-sylwester','cat' => 'patagonia', 'scope' => $s, 'label' => 'Zakupy 30.12', 'hint' => '1 stycznia sklepy są zamknięte.' ),
		array( 'id' => 'wiatr',         'cat' => 'patagonia', 'scope' => $s, 'label' => 'Prognoza wiatru przed każdym dniem w parku', 'link' => 'https://www.windy.com' ),
		array( 'id' => 'prowiant',      'cat' => 'patagonia', 'scope' => $s, 'label' => 'Prowiant i woda na Base de las Torres', 'hint' => 'Ok. 20 km, 8–10 h marszu.' ),

		// Atacama.
		array( 'id' => 'terminy-alen',  'cat' => 'atacama', 'scope' => $s, 'label' => 'Wpisać terminy wycieczek po Atacamie', 'hint' => 'Przewodnik: {przewodnik}. Ania ma szczegóły — daty wpisuje się przyciskiem „Edytuj” przy wycieczce w planie.' ),
		array( 'id' => 'aklimatyzacja', 'cat' => 'atacama', 'scope' => $s, 'label' => 'Kolejność wycieczek pod aklimatyzację', 'hint' => 'Najpierw nisko (Valle de la Luna), El Tatio w środku, Cerro Toco na końcu. O lekach na wysokość — zapytać lekarza przed wyjazdem.' ),
		array( 'id' => 'gotowka-spa',   'cat' => 'atacama', 'scope' => $s, 'label' => 'Gotówka na San Pedro', 'hint' => 'Bankomaty bywają puste.' ),
		array( 'id' => 'tatio-cieplo',  'cat' => 'atacama', 'scope' => $s, 'label' => 'Na El Tatio i niebo nocą: puchówka, czapka, rękawiczki', 'hint' => 'O świcie bywa kilka stopni poniżej zera.' ),
		array( 'id' => 'woda-spa',      'cat' => 'atacama', 'scope' => $s, 'label' => 'Woda butelkowana w San Pedro' ),

		// Wybrzeże.
		array( 'id' => 'valpo-dzien',   'cat' => 'ocean', 'scope' => $s, 'label' => 'Wybrać dzień na Valparaíso (13 lub 14.01)' ),
		array( 'id' => 'valpo-kieszonkowcy','cat' => 'ocean', 'scope' => $s, 'label' => 'Valparaíso: telefon schowany, drobne na ascensores' ),
		array( 'id' => 'dojazd-lotnisko','cat' => 'ocean', 'scope' => $s, 'label' => 'Plan dojazdu na lotnisko 16.01 (wylot 11:45)' ),
		array( 'id' => 'odprawa-powrot','cat' => 'ocean', 'scope' => $s, 'label' => 'Odprawa online na lot powrotny (15.01)' ),

		// Pakowanie.
		array( 'id' => 'warstwy',       'cat' => 'pakowanie', 'scope' => $s, 'label' => 'Warstwy: bielizna termiczna, polar, puchówka' ),
		array( 'id' => 'kurtka',        'cat' => 'pakowanie', 'scope' => $s, 'label' => 'Kurtka przeciwdeszczowa i wiatroszczelna' ),
		array( 'id' => 'buty',          'cat' => 'pakowanie', 'scope' => $s, 'label' => 'Rozchodzone buty trekkingowe' ),
		array( 'id' => 'kijki',         'cat' => 'pakowanie', 'scope' => $s, 'label' => 'Kijki trekkingowe (do bagażu rejestrowanego)' ),
		array( 'id' => 'kapiel',        'cat' => 'pakowanie', 'scope' => $s, 'label' => 'Stroje kąpielowe i klapki' ),
		array( 'id' => 'slonce',        'cat' => 'pakowanie', 'scope' => $s, 'label' => 'Krem SPF 50, pomadka z filtrem, okulary kat. 3–4', 'hint' => 'Na altiplano i lodowcu słońce pali mocno.' ),
		array( 'id' => 'czapki',        'cat' => 'pakowanie', 'scope' => $s, 'label' => 'Czapka z daszkiem, czapka zimowa, rękawiczki, buff' ),
		array( 'id' => 'czolowka',      'cat' => 'pakowanie', 'scope' => $s, 'label' => 'Czołówki z czerwonym światłem', 'hint' => 'Na obserwację nieba i wyjście na El Tatio.' ),
		array( 'id' => 'adapter',       'cat' => 'pakowanie', 'scope' => $s, 'label' => 'Adapter do gniazdek typu C/L', 'hint' => 'Płaskie wtyczki pasują, grube polskie (Schuko) nie. 220 V.' ),
		array( 'id' => 'powerbank',     'cat' => 'pakowanie', 'scope' => $s, 'label' => 'Powerbank — tylko w bagażu podręcznym' ),
		array( 'id' => 'noc-autobus',   'cat' => 'pakowanie', 'scope' => $s, 'label' => 'Zatyczki, opaski na oczy, poduszki na nocny autobus i loty' ),
		array( 'id' => 'plecaki',       'cat' => 'pakowanie', 'scope' => $s, 'label' => 'Plecaki dzienne i butelki na wodę' ),
		array( 'id' => 'lokomocja',     'cat' => 'pakowanie', 'scope' => $s, 'label' => 'Tabletki na chorobę lokomocyjną', 'hint' => 'Rejs do lodowca Grey, kręte drogi.' ),
		array( 'id' => 'apteczka',      'cat' => 'pakowanie', 'scope' => $s, 'label' => 'Apteczka: plastry na odciski, przeciwbólowe, elektrolity' ),
		array( 'id' => 'ladowarki',     'cat' => 'pakowanie', 'scope' => $p, 'label' => 'Telefon, ładowarka, okulary' ),
		array( 'id' => 'ubrania',       'cat' => 'pakowanie', 'scope' => $p, 'label' => 'Swoje ubrania spakowane' ),
	);
}

/** Apps and sites worth having on the phone. */
function mmcl_apps() {
	return array(
		array( 'name' => 'WhatsApp',       'what' => 'W Chile wszyscy piszą przez WhatsApp — gospodarze, przewodnicy, agencje', 'url' => 'https://www.whatsapp.com' ),
		array( 'name' => 'Uber / Cabify',  'what' => 'Przejazdy w Santiago i Viña', 'url' => 'https://www.cabify.com' ),
		array( 'name' => 'Recorrido.cl',   'what' => 'Bilety autobusowe (tu kupiony JAC)', 'url' => 'https://www.recorrido.cl' ),
		array( 'name' => 'LATAM / SKY / JetSMART', 'what' => 'Loty krajowe', 'url' => 'https://www.latamairlines.com' ),
		array( 'name' => 'Pases Parques',  'what' => 'Bilety do parków CONAF', 'url' => 'https://www.pasesparques.cl' ),
		array( 'name' => 'Ingreso a Chile','what' => 'Cyfrowa deklaracja SAG/Aduana', 'url' => 'https://www.ingresoachile.cl' ),
		array( 'name' => 'Google Maps',    'what' => 'Mapy offline całej trasy', 'url' => 'https://maps.google.com' ),
		array( 'name' => 'Google Tłumacz', 'what' => 'Hiszpański offline i z aparatu', 'url' => 'https://translate.google.com' ),
		array( 'name' => 'Windy',          'what' => 'Wiatr w Patagonii, chmury nad Atacamą', 'url' => 'https://www.windy.com' ),
		array( 'name' => 'Sernageomin',    'what' => 'Alert wulkanu Villarrica', 'url' => 'https://rnvv.sernageomin.cl' ),
		array( 'name' => 'Turkish Airlines','what' => 'Odprawa i karty pokładowe', 'url' => 'https://www.turkishairlines.com' ),
		array( 'name' => 'Revolut / Wise', 'what' => 'Płatności i wypłaty w CLP', 'url' => 'https://wise.com' ),
	);
}

/** Practical facts. Checked 25.10.2026. */
function mmcl_facts() {
	return array(
		'fx_default'  => 253.1, // CLP for 1 PLN, mid-market 25.10.2026.
		'time'        => 'Chile (Santiago i Patagonia): UTC−3. W Polsce jest o 4 godziny później.',
		'power'       => 'Gniazdka typu C i L, 220 V. Płaskie wtyczki pasują, grube polskie potrzebują adaptera.',
		'tips'        => 'W restauracjach zwykle 10% napiwku — rachunek często go proponuje („propina”).',
		'emergency'   => array(
			array( 'n' => '131', 'what' => 'Pogotowie (SAMU)' ),
			array( 'n' => '132', 'what' => 'Straż pożarna' ),
			array( 'n' => '133', 'what' => 'Policja (Carabineros)' ),
			array( 'n' => '134', 'what' => 'Policja śledcza (PDI)' ),
		),
		'embassy'     => array(
			'name'    => 'Ambasada RP w Santiago',
			'address' => 'Mar del Plata 2055, Providencia, Santiago',
			'phone'   => '+56 22 824 5000',
			'duty'    => '+56 9 9139 0087',
			'url'     => 'https://www.gov.pl/web/chile',
		),
		'holidays'    => '25 grudnia i 1 stycznia supermarkety i centra handlowe są zamknięte z mocy prawa. Restauracje, stacje benzynowe i apteki dyżurne działają.',
	);
}
