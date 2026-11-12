/**
 * Chile 2026 — panel podróży.
 * Renders the dashboard from the JSON payload printed by [chile-dashboard]
 * and keeps the checklist, bookings and dates in sync through mm-chile/v1.
 */
( function () {
	'use strict';

	document.querySelectorAll( '[data-mmcl]' ).forEach( function ( root ) {
		try {
			init( root );
		} catch ( err ) {
			root.insertAdjacentHTML( 'beforeend', '<p class="mmcl-noscript">Nie udało się wczytać panelu. Odśwież stronę.</p>' );
			if ( window.console ) { console.error( err ); } // eslint-disable-line no-console
		}
	} );

	function init( root ) {
		var P = JSON.parse( root.querySelector( '.mmcl-data' ).textContent );
		var PRIV = !! P.private;
		var TRIP = P.trip;
		var STAGE_BY_ID = {};
		P.stages.forEach( function ( s ) { STAGE_BY_ID[ s.id ] = s; } );
		var PLACE_BY_ID = {};
		P.places.forEach( function ( p ) { PLACE_BY_ID[ p.id ] = p; } );
		var TRAV = P.travellers || [];

		var S = {
			state: P.state || { ticks: {}, overrides: {}, custom: [] },
			cat: store( 'cat' ) || ( P.cats ? P.cats[ 0 ].id : '' ),
			hideDone: store( 'hideDone' ) === '1',
			scale: store( 'scale' ) || P.scale
		};
		if ( P.cats && ! P.cats.some( function ( c ) { return c.id === S.cat; } ) ) { S.cat = P.cats[ 0 ].id; }

		/* ——— small helpers ——— */

		function store( k, v ) {
			var key = 'mmcl-' + k;
			try {
				if ( v === undefined ) { return window.localStorage.getItem( key ); }
				window.localStorage.setItem( key, v );
			} catch ( e ) { /* private mode */ }
			return null;
		}
		function esc( s ) {
			return String( s == null ? '' : s ).replace( /[&<>"']/g, function ( c ) {
				return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ c ];
			} );
		}
		function stageVar( stage ) {
			var s = STAGE_BY_ID[ stage ];
			return 'var(--mmcl-' + ( s ? s.color : 'ink' ) + ')';
		}
		function utc( ymd ) { return new Date( ymd + 'T12:00:00Z' ); }
		function fmt( ymd, o ) {
			o = Object.assign( { timeZone: 'UTC' }, o );
			return new Intl.DateTimeFormat( 'pl-PL', o ).format( utc( ymd ) );
		}
		function fmtDay( ymd ) { return fmt( ymd, { weekday: 'short', day: 'numeric', month: 'short' } ); }
		function fmtDM( ymd ) { return fmt( ymd, { day: 'numeric', month: 'long' } ); }
		function fmtShort( ymd ) { return fmt( ymd, { day: 'numeric', month: 'numeric' } ); }
		function addDays( ymd, n ) {
			var d = utc( ymd );
			d.setUTCDate( d.getUTCDate() + n );
			return d.toISOString().slice( 0, 10 );
		}
		function dayN( ymd ) { return Math.round( ( utc( ymd ) - utc( TRIP.first_day ) ) / 864e5 ) + 1; }
		var TOTAL_DAYS = dayN( TRIP.last_day );
		function ymdIn( tz, date ) {
			var parts = new Intl.DateTimeFormat( 'en-CA', { timeZone: tz, year: 'numeric', month: '2-digit', day: '2-digit' } ).formatToParts( date || new Date() );
			var m = {};
			parts.forEach( function ( p ) { m[ p.type ] = p.value; } );
			return m.year + '-' + m.month + '-' + m.day;
		}
		function timeIn( tz ) {
			return new Intl.DateTimeFormat( 'pl-PL', { timeZone: tz, hour: '2-digit', minute: '2-digit' } ).format( new Date() );
		}
		function phase() {
			var now = Date.now();
			if ( now < Date.parse( TRIP.start ) ) { return 'before'; }
			if ( now > Date.parse( TRIP.end ) ) { return 'after'; }
			return 'during';
		}
		function todayTrip() {
			// During the trip "today" is the date in Chile (Warsaw for the travel days at both ends is close enough).
			return ymdIn( TRIP.chile_tz );
		}
		function plDays( n ) { return n === 1 ? 'dzień' : 'dni'; }
		function stageOfDate( ymd ) {
			for ( var i = 0; i < P.stages.length; i++ ) {
				if ( ymd >= P.stages[ i ].from && ymd <= P.stages[ i ].to ) { return P.stages[ i ]; }
			}
			return null;
		}

		/* ——— icons ——— */
		var IC = {
			flight: '<path d="M17.8 19.2 16 11l3.5-3.5C21 6 21.5 4 21 3c-1-.5-3 0-4.5 1.5L13 8 4.8 6.2c-.5-.1-.9.1-1.1.5l-.3.5c-.2.5-.1 1 .3 1.3L9 12l-2 3H4l-1 1 3 2 2 3 1-1v-3l3-2 3.5 5.3c.3.4.8.5 1.3.3l.5-.2c.4-.3.6-.7.5-1.2z"/>',
			bus: '<rect x="4" y="3" width="16" height="15" rx="2.5"/><path d="M4 11h16M8 18v2.5M16 18v2.5"/><circle cx="8" cy="14.5" r=".8"/><circle cx="16" cy="14.5" r=".8"/>',
			stay: '<path d="M3 18V7M3 13h18v5M21 13v-2.5A2.5 2.5 0 0 0 18.5 8H11v5"/><circle cx="7" cy="10.5" r="1.8"/>',
			activity: '<path d="M2.5 19.5l6.5-12 4 6.5 2.5-3.5 6 9z"/><path d="M7.5 10.5l1.5 1.5 1.5-1"/>',
			car: '<path d="M4 16.5V12l2-5h12l2 5v4.5M4 16.5h16M3 12h18"/><circle cx="7.5" cy="16.5" r="1.8"/><circle cx="16.5" cy="16.5" r="1.8"/>',
			transfer: '<path d="M3 8h14l-3-3M21 16H7l3 3"/>',
			info: '<circle cx="12" cy="12" r="9"/><path d="M12 11v6M12 7.5v.5"/>',
			task: '<rect x="4" y="4" width="16" height="16" rx="3"/><path d="M8.5 12.5l2.5 2.5 4.5-5.5"/>',
			free: '<circle cx="12" cy="12" r="4"/><path d="M12 2.5v2M12 19.5v2M2.5 12h2M19.5 12h2M5.3 5.3l1.4 1.4M17.3 17.3l1.4 1.4M5.3 18.7l1.4-1.4M17.3 6.7l1.4-1.4"/>',
			star: '<path d="M12 2l2.6 4.2 4.5-1.3-1.3 4.5L22 12l-4.2 2.6 1.3 4.5-4.5-1.3L12 22l-2.6-4.2-4.5 1.3 1.3-4.5L2 12l4.2-2.6-1.3-4.5 4.5 1.3z"/>',
			gear: '<circle cx="12" cy="12" r="3"/><path d="M12 2.5v3M12 18.5v3M2.5 12h3M18.5 12h3M5.3 5.3l2.1 2.1M16.6 16.6l2.1 2.1M5.3 18.7l2.1-2.1M16.6 7.4l2.1-2.1"/>',
			check: ''
		};
		function icon( name, cls ) {
			return '<svg class="' + ( cls || '' ) + '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' + ( IC[ name ] || IC.info ) + '</svg>';
		}
		function evIcon( e ) { return icon( e.id === 'stargazing' ? 'star' : e.type, 'mmcl-ev__icon' ); }
		function tickSvg() {
			return '<svg viewBox="0 0 20 20" aria-hidden="true"><rect class="box" x="1.5" y="1.5" width="17" height="17" rx="3"/><path class="mark" d="M5.5 10.5l3 3 6-7"/></svg>';
		}

		/* Woven band: stepped diamonds, Mapuche ñimin style. */
		var bandSeq = 0;
		function band( bg, fg, accent ) {
			var id = 'mmcl-band-' + ( ++bandSeq ) + '-' + Math.random().toString( 36 ).slice( 2, 7 );
			return '<span class="mmcl-band" aria-hidden="true"><svg width="100%" height="22"><defs><pattern id="' + id + '" width="40" height="22" patternUnits="userSpaceOnUse">' +
				'<rect width="40" height="22" style="fill:' + bg + '"/>' +
				'<rect width="40" height="2" style="fill:' + accent + '"/><rect y="20" width="40" height="2" style="fill:' + accent + '"/>' +
				'<path d="M18 3h4v2h2v2h2v2h2v4h-2v2h-2v2h-2v2h-4v-2h-2v-2h-2v-2h-2V9h2V7h2V5h2z" style="fill:' + fg + '"/>' +
				'<rect x="18" y="9" width="4" height="4" style="fill:' + bg + '"/>' +
				'<rect x="0" y="9" width="2" height="4" style="fill:' + accent + '"/><rect x="38" y="9" width="2" height="4" style="fill:' + accent + '"/>' +
				'</pattern></defs><rect width="100%" height="22" fill="url(#' + id + ')"/></svg></span>';
		}

		/* ——— data views ——— */

		function events() {
			var ov = S.state.overrides || {};
			return P.events.map( function ( e, i ) {
				var m = Object.assign( {}, e, { idx: i } );
				var o = ov[ e.id ];
				if ( o ) {
					[ 'date', 'time', 'end_date', 'end_time', 'note', 'status' ].forEach( function ( k ) {
						if ( Object.prototype.hasOwnProperty.call( o, k ) ) {
							m[ k ] = o[ k ] === '' && k !== 'note' ? null : o[ k ];
						}
					} );
					if ( typeof o.title === 'string' && o.title.trim() ) { m.title = o.title; }
					if ( typeof o.important === 'boolean' ) { m.important = o.important; }
					if ( typeof o.done === 'boolean' ) { m.done = o.done; }
				}
				m.origTitle = e.title;
				return m;
			} );
		}
		function evById( id ) {
			return events().filter( function ( e ) { return e.id === id; } )[ 0 ];
		}
		function sortEv( a, b ) {
			var ka = ( a.date || '9999' ) + ( a._t || a.time || '99:99' );
			var kb = ( b.date || '9999' ) + ( b._t || b.time || '99:99' );
			return ka < kb ? -1 : ka > kb ? 1 : a.idx - b.idx;
		}
		/** Events happening on a given day, incl. arrivals of overnight journeys. */
		function eventsOn( ymd, list ) {
			var out = [];
			list.forEach( function ( e ) {
				if ( e.date === ymd ) { out.push( e ); }
				if ( e.end_date === ymd && e.date !== ymd && /flight|bus|transfer/.test( e.type ) ) {
					out.push( Object.assign( {}, e, { arrival: true, date: ymd, _t: e.end_time || '00:01' } ) );
				}
			} );
			return out.sort( sortEv );
		}
		function staysOverNight( ymd, list ) {
			return list.filter( function ( e ) {
				return e.type === 'stay' && e.date && e.end_date && e.date < ymd && ymd < e.end_date;
			} );
		}

		function statusChips( e ) {
			var h = '';
			if ( e.important && ! e.done ) { h += '<span class="mmcl-chip mmcl-chip--important">Ważne</span>'; }
			if ( e.status === 'booked' ) { h += '<span class="mmcl-chip mmcl-chip--booked">Zarezerwowane</span>'; }
			if ( e.status === 'todo' ) { h += '<span class="mmcl-chip mmcl-chip--todo">Do zarezerwowania</span>'; }
			if ( ! e.date && ! e.arrival ) { h += '<span class="mmcl-chip mmcl-chip--tbc">Termin do ustalenia</span>'; }
			return h;
		}
		function editBtn( e, label ) {
			if ( ! PRIV || e.arrival ) { return ''; }
			return '<button type="button" class="mmcl-link-btn" data-act="edit" data-id="' + esc( e.id ) + '" data-fk="edit-' + esc( e.id ) + '">' + ( label || 'Edytuj' ) + '<span class="mmcl-sr"> — ' + esc( e.title ) + '</span></button>';
		}
		function doneBtn( e ) {
			if ( ! PRIV || e.type !== 'task' || e.arrival ) { return ''; }
			return '<button type="button" class="mmcl-tick" aria-pressed="' + ( !! e.done ) + '" data-act="task-done" data-id="' + esc( e.id ) + '" data-fk="done-' + esc( e.id ) + '">' + tickSvg() + 'Zrobione<span class="mmcl-sr">: ' + esc( e.title ) + '</span></button>';
		}
		function linkHtml( e ) {
			return e.link ? ' <a href="' + esc( e.link ) + '" target="_blank" rel="noopener">strona</a>' : '';
		}

		function evRow( e, opts ) {
			opts = opts || {};
			var time = '';
			var arrWord = e.type === 'flight' ? 'Przylot' : 'Przyjazd';
			if ( e.arrival ) {
				time = ( e.end_time ? esc( e.end_time ) : '' ) + '<small>' + arrWord.toLowerCase() + '</small>';
			} else if ( e.time ) {
				time = esc( e.time );
			}
			if ( opts.withDate && e.date ) {
				time = '<small>' + esc( fmtDay( e.date ) ) + '</small>' + time;
			}
			var title = e.arrival ? ( arrWord + ': ' + e.title ) : e.title;
			var extra = '';
			if ( ! e.arrival && e.end_date && e.type === 'stay' ) {
				extra = 'Do ' + fmtDay( e.end_date );
			} else if ( ! e.arrival && e.end_date && e.type === 'car' ) {
				extra = 'Zwrot ' + fmtDay( e.end_date ) + ( e.end_time ? ', ' + e.end_time : '' );
			} else if ( ! e.arrival && e.end_date && e.end_date !== e.date ) {
				extra = arrWord + ' ' + fmtDay( e.end_date ) + ( e.end_time ? ', ' + e.end_time : '' );
			} else if ( ! e.arrival && e.end_time ) {
				extra = arrWord + ' o ' + e.end_time;
			}
			var place = [ e.place, extra ].filter( Boolean ).join( '. ' );
			var cls = ( e.type === 'free' ? ' mmcl-ev--free' : '' ) + ( e.type === 'task' ? ' mmcl-ev--task' : '' ) + ( e.done ? ' is-done' : '' ) + ( e.important && ! e.done ? ' is-important' : '' );
			return '<div class="mmcl-ev' + cls + '" style="--stage:' + stageVar( e.stage ) + '">' +
				'<div class="mmcl-ev__time">' + time + '</div>' +
				'<div class="mmcl-ev__main">' +
					'<div class="mmcl-ev__title">' + evIcon( e ) + '<span>' + esc( title ) + '</span></div>' +
					( place ? '<div class="mmcl-ev__place">' + esc( place ) + '</div>' : '' ) +
					( e.detail && ! e.arrival ? '<div class="mmcl-ev__detail">' + esc( e.detail ) + linkHtml( e ) + '</div>' : ( ! e.arrival && e.link ? '<div class="mmcl-ev__detail">' + linkHtml( e ) + '</div>' : '' ) ) +
					( PRIV && e.note && ! e.arrival && ! opts.noNote ? '<div class="mmcl-ev__note">' + esc( e.note ) + '</div>' : '' ) +
					'<div class="mmcl-ev__meta">' + ( e.arrival ? '' : doneBtn( e ) + statusChips( e ) + editBtn( e ) ) + '</div>' +
				'</div>' +
			'</div>';
		}

		/* ——— sections ——— */

		function heroHtml() {
			var ph = phase();
			var names = TRAV.map( function ( t ) { return t.name; } );
			var who = PRIV && names.length ? names.join( ' i ' ) + ', ' : '';
			var left = '<div>' +
				'<span class="mmcl-stamp">¡Buen viaje!</span>' +
				'<h1 class="mmcl-hero__title">Chile<span class="mmcl-hero__year">grudzień 2026 – styczeń 2027</span></h1>' +
				'<p class="mmcl-hero__who">' + esc( who ) + '20 grudnia – 17 stycznia. ' + TOTAL_DAYS + ' dni od pustyni Atacama po lodowce Patagonii.</p>' +
			'</div>';
			var right = '<div class="mmcl-count" data-count>' + countHtml( ph ) + '</div>';
			return '<header class="mmcl-hero">' +
				band( 'var(--mmcl-copihue)', 'var(--mmcl-ochre)', 'var(--mmcl-ink)' ) +
				'<div class="mmcl-hero__inner">' + left + right + '</div>' +
				'<div class="mmcl-ribbon">' + ribbonSvg( false ) + ribbonSvg( true ) + ribbonLegend() + '</div>' +
			'</header>';
		}
		function countHtml( ph ) {
			if ( ph === 'before' ) {
				var ms = Date.parse( TRIP.start ) - Date.now();
				var d = Math.floor( ms / 864e5 );
				var rest = ms - d * 864e5;
				var hh = Math.floor( rest / 36e5 ), mm = Math.floor( ( rest % 36e5 ) / 6e4 ), ss = Math.floor( ( rest % 6e4 ) / 1e3 );
				var pad = function ( n ) { return ( n < 10 ? '0' : '' ) + n; };
				return '<div class="mmcl-count__lead">Do wylotu zostało</div>' +
					'<div class="mmcl-count__big">' + d + ' ' + plDays( d ) + '</div>' +
					'<div class="mmcl-count__clock" aria-hidden="true">i ' + pad( hh ) + ':' + pad( mm ) + ':' + pad( ss ) + '</div>' +
					'<div class="mmcl-count__when">Niedziela, 20 grudnia, 12:50 z Warszawy. Lądujemy w Santiago 21 grudnia o 10:00.</div>';
			}
			if ( ph === 'during' ) {
				var today = todayTrip();
				var st = stageOfDate( today );
				var todays = eventsOn( today, events() );
				return '<div class="mmcl-count__lead">Dzień ' + Math.max( 1, Math.min( TOTAL_DAYS, dayN( today ) ) ) + ' z ' + TOTAL_DAYS + ', ' + esc( fmtDM( today ) ) + '</div>' +
					'<div class="mmcl-count__big">' + esc( st ? st.name : 'W drodze' ) + '</div>' +
					( todays.length ? '<ul class="mmcl-count__today">' + todays.map( function ( e ) {
						return '<li>' + ( e.time ? esc( e.time ) + ' ' : '' ) + esc( e.arrival ? ( e.type === 'flight' ? 'Przylot: ' : 'Przyjazd: ' ) + e.title : e.title ) + '</li>';
					} ).join( '' ) + '</ul>' : '<div class="mmcl-count__when">Dziś nic nie jest zaplanowane.</div>' );
			}
			return '<div class="mmcl-count__lead">Wróciliśmy 17 stycznia</div>' +
				'<div class="mmcl-count__big">' + TOTAL_DAYS + ' dni</div>' +
				'<div class="mmcl-count__when">Santiago, Pucón, Patagonia, Atacama i Pacyfik.</div>';
		}

		/*
		 * Route ribbon: places on a north–south axis, legs as arcs in travel order.
		 * Wide containers get a horizontal ribbon (north left); narrow ones a vertical one
		 * (north on top), like the map of Chile. Geometry is computed along the axis (u)
		 * and across it (v), then mapped to x/y.
		 */
		function ribbonSvg( vertical ) {
			var N = -21, SO = -53;
			var G = vertical ?
				{ W: 400, H: 770, u0: 50, len: 680, line: 150, flightDir: -1, cap: 118, nameV: 232, leadTo: 224 } :
				{ W: 1200, H: 262, u0: 60, len: 1080, line: 168, flightDir: -1, cap: 128, nameV: 246, leadTo: 226 };
			var along = function ( lat ) { return G.u0 + ( N - lat ) / ( N - SO ) * G.len; };
			var xy = function ( u, v ) { return vertical ? { x: v, y: u } : { x: u, y: v }; };
			var P2 = function ( u, v ) { var q = xy( u, v ); return q.x.toFixed( 1 ) + ' ' + q.y.toFixed( 1 ); };
			var here = null;
			if ( phase() === 'during' ) {
				var st = stageOfDate( todayTrip() );
				var map = { santiago: 'santiago', pucon: 'pucon', patagonia: 'natales', atacama: 'spa', ocean: 'vina' };
				here = st ? map[ st.id ] : null;
			}
			var tid = 'mmcl-rt-' + ( vertical ? 'v' : 'h' );
			var h = '<svg class="mmcl-ribbon__' + ( vertical ? 'v' : 'h' ) + '" viewBox="0 0 ' + G.W + ' ' + G.H + '" role="img" aria-labelledby="' + tid + '"><title id="' + tid + '">Trasa z północy na południe: San Pedro de Atacama, Viña del Mar, Santiago, Pucón, Puerto Montt, Puerto Natales</title>';

			// axis
			var a0 = xy( G.u0 - 20, G.line ), a1 = xy( G.u0 + G.len + 20, G.line );
			h += '<line x1="' + a0.x + '" y1="' + a0.y + '" x2="' + a1.x + '" y2="' + a1.y + '" stroke="#8f99ab" stroke-width="2"/>';
			for ( var lat = -25; lat >= -50; lat -= 5 ) {
				var t1 = xy( along( lat ), G.line - 5 ), t2 = xy( along( lat ), G.line + 5 );
				h += '<line x1="' + t1.x + '" y1="' + t1.y + '" x2="' + t2.x + '" y2="' + t2.y + '" stroke="#5d6a82" stroke-width="1"/>';
				if ( ! vertical ) {
					h += '<text class="mmcl-ribbon__axis" x="' + along( lat ) + '" y="' + ( G.line - 10 ) + '" text-anchor="middle">' + ( -lat ) + '°S</text>';
				}
			}
			if ( vertical ) {
				h += '<text class="mmcl-ribbon__axis" x="' + G.line + '" y="18" text-anchor="middle">północ</text>';
				h += '<text class="mmcl-ribbon__axis" x="' + G.line + '" y="' + ( G.H - 6 ) + '" text-anchor="middle">południe</text>';
			} else {
				h += '<text class="mmcl-ribbon__axis" x="' + G.u0 + '" y="' + ( G.line - 14 ) + '" text-anchor="start">północ</text>';
				h += '<text class="mmcl-ribbon__axis" x="' + ( G.u0 + G.len ) + '" y="' + ( G.line - 14 ) + '" text-anchor="end">południe</text>';
			}

			// legs
			var seqs = '';
			P.legs.forEach( function ( l, i ) {
				var a = PLACE_BY_ID[ l.from ], b = PLACE_BY_ID[ l.to ];
				var u1 = along( a.lat ), u2 = along( b.lat ), du = Math.abs( u2 - u1 );
				var north = b.lat > a.lat;
				var flight = l.mode === 'flight';
				var depth = flight ? Math.min( G.cap, 30 + du * 0.17 ) - ( north ? 22 : 0 ) : 16 + du * 0.12 + ( north ? 22 : 0 );
				var dir = flight ? G.flightDir : -G.flightDir;
				var mu = ( u1 + u2 ) / 2;
				var col = flight ? 'var(--mmcl-glaciar-l)' : 'var(--mmcl-ochre)';
				h += '<path d="M' + P2( u1, G.line ) + ' Q' + P2( mu, G.line + dir * depth * 2 ) + ' ' + P2( u2, G.line ) + '" fill="none" stroke="' + col + '" stroke-width="' + ( flight ? 2 : 3 ) + '"' + ( flight ? ' stroke-dasharray="6 6"' : '' ) + '/>';
				var c = xy( mu, G.line + dir * depth );
				seqs += '<g><title>' + ( i + 1 ) + '. ' + esc( a.name ) + ' → ' + esc( b.name ) + ', ' + esc( fmtDay( l.date ) ) + ( flight ? ' (lot)' : ' (droga)' ) + '</title>' +
					'<circle cx="' + c.x.toFixed( 1 ) + '" cy="' + c.y.toFixed( 1 ) + '" r="10" fill="' + col + '"/>' +
					'<text class="mmcl-ribbon__seq" x="' + c.x.toFixed( 1 ) + '" y="' + ( c.y + 4 ).toFixed( 1 ) + '" text-anchor="middle">' + ( i + 1 ) + '</text></g>';
			} );
			h += seqs;

			// places
			var anchorsH = { vina: 'end', santiago: 'start', pucon: 'end', pmontt: 'start' };
			var nudgeV = { vina: -7, santiago: 11 };
			P.places.forEach( function ( p ) {
				var pu = along( p.lat );
				var dot = xy( pu, G.line );
				var l1 = xy( pu, G.line + 10 ), l2 = xy( pu, G.leadTo );
				var tx, ty, anchor;
				if ( vertical ) {
					tx = G.nameV; ty = pu + 5 + ( nudgeV[ p.id ] || 0 ); anchor = 'start';
				} else {
					anchor = anchorsH[ p.id ] || 'middle';
					tx = anchor === 'end' ? pu - 3 : anchor === 'start' ? pu + 3 : pu; ty = G.nameV;
				}
				h += '<g class="mmcl-ribbon__node" tabindex="0" role="link" data-act="goto-stage" data-stage="' + esc( p.stage ) + '" aria-label="' + esc( p.name ) + ' — pokaż w planie">';
				if ( here === p.id ) {
					h += '<circle class="mmcl-ribbon__here" cx="' + dot.x + '" cy="' + dot.y + '" r="13" fill="var(--mmcl-ochre)"/>';
				}
				h += '<line x1="' + l1.x + '" y1="' + l1.y + '" x2="' + l2.x + '" y2="' + l2.y + '" stroke="#5d6a82" stroke-width="1" stroke-dasharray="2 3"/>';
				h += '<circle class="dot" cx="' + dot.x + '" cy="' + dot.y + '" r="8" fill="' + stageVar( p.stage ) + '" stroke="var(--mmcl-paper)" stroke-width="3"/>';
				h += '<text class="mmcl-ribbon__name" x="' + tx + '" y="' + ty + '" text-anchor="' + anchor + '">' + esc( p.name ) + ( here === p.id ? ' — jesteśmy tu' : '' ) + '</text>';
				h += '</g>';
			} );
			return h + '</svg>';
		}
		function ribbonLegend() {
			return '<div class="mmcl-ribbon__legend"><span class="is-flight"><i></i>lot</span><span class="is-ground"><i></i>autobus lub transfer</span><span>Numery to kolejność przejazdów. Ok. 3200 km z północy na południe.</span></div>';
		}

		function navHtml() {
			var todo = PRIV ? bookingTodo().length : 0;
			var items = [
				[ 'mmcl-next', 'Co dalej' ],
				PRIV ? [ 'mmcl-book', 'Do zarezerwowania', todo ] : null,
				[ 'mmcl-plan', 'Plan dzień po dniu' ],
				[ 'mmcl-travel', 'Loty i przejazdy' ],
				PRIV ? [ 'mmcl-list', 'Lista' ] : null,
				[ 'mmcl-info', 'Na miejscu' ],
				[ 'mmcl-apps', 'Aplikacje' ],
				PRIV ? [ 'mmcl-contacts', 'Kontakty' ] : null
			].filter( Boolean );
			return '<nav class="mmcl-nav" aria-label="Sekcje panelu"><ul>' + items.map( function ( it ) {
				return '<li><a href="#' + it[ 0 ] + '">' + esc( it[ 1 ] ) + ( it[ 2 ] ? '<span class="mmcl-nav__count">' + it[ 2 ] + '</span>' : '' ) + '</a></li>';
			} ).join( '' ) + '</ul></nav>';
		}

		function sectionHead( title, stamp, aside ) {
			return '<div class="mmcl-section__head"><h2 class="mmcl-section__title">' + esc( title ) + '</h2>' +
				( aside ? '<span class="mmcl-section__aside">' + aside + '</span>' : '' ) +
				( stamp ? '<span class="mmcl-stamp">' + esc( stamp ) + '</span>' : '' ) + '</div>';
		}

		function nextHtml() {
			var ph = phase();
			var from = ph === 'during' ? todayTrip() : TRIP.first_day;
			var list = events().filter( function ( e ) { return e.date && e.type !== 'free'; } );
			var days = [];
			var d = from;
			var shown = 0;
			while ( d <= TRIP.last_day && shown < 7 ) {
				var on = eventsOn( d, list );
				if ( on.length ) {
					days.push( { d: d, ev: on } );
					shown += on.length;
				}
				d = addDays( d, 1 );
			}
			var body;
			if ( ph === 'after' ) {
				body = '<p class="mmcl-empty">Podróż zakończona. Plan zostaje poniżej na pamiątkę.</p>';
			} else {
				body = days.map( function ( g ) {
					return '<h3 class="mmcl-next__day">' + esc( fmtDay( g.d ) ) + ( ph === 'during' && g.d === todayTrip() ? ' — dziś' : '' ) + '</h3>' +
						g.ev.map( function ( e ) { return evRow( e, { noNote: true } ); } ).join( '' );
				} ).join( '' );
			}
			return '<section class="mmcl-section" id="mmcl-next">' + sectionHead( 'Co dalej', 'Próximo' ) +
				'<div class="mmcl-grid-2"><div>' + body + '</div>' + factsHtml() + '</div></section>';
		}

		function factsHtml() {
			return '<aside class="mmcl-facts" aria-label="Czas i pieniądze">' +
				'<h3>Teraz w Santiago</h3>' +
				'<div class="mmcl-clock" data-clock>' + esc( timeIn( TRIP.chile_tz ) ) + '</div>' +
				'<p class="mmcl-section__aside">W Warszawie: <span data-clock-waw>' + esc( timeIn( 'Europe/Warsaw' ) ) + '</span></p>' +
				'<div class="mmcl-fx">' +
					'<div><label for="mmcl-fx-pln">Złote</label><input id="mmcl-fx-pln" inputmode="decimal" data-fx="pln" value="100"></div>' +
					'<span>=</span>' +
					'<div><label for="mmcl-fx-clp">Peso chilijskie</label><input id="mmcl-fx-clp" inputmode="decimal" data-fx="clp" value="' + Math.round( 100 * P.fx ) + '"></div>' +
					'<p class="mmcl-fx__note">1 zł ≈ ' + esc( String( P.fx ).replace( '.', ',' ) ) + ' CLP. Kurs zmienisz w ustawieniach.</p>' +
				'</div>' +
			'</aside>';
		}

		function bookingTodo() {
			return events().filter( function ( e ) { return e.booking && e.status === 'todo'; } ).sort( sortEv );
		}
		function bookHtml() {
			if ( ! PRIV ) { return ''; }
			var todo = bookingTodo();
			var done = events().filter( function ( e ) { return e.booking && e.status === 'booked'; } ).sort( sortEv );
			var groups = [];
			P.stages.forEach( function ( s ) {
				var items = todo.filter( function ( e ) { return e.stage === s.id; } );
				if ( items.length ) { groups.push( { s: s, items: items } ); }
			} );
			var body = groups.length ? '<div class="mmcl-book">' + groups.map( function ( g ) {
				return '<div class="mmcl-book__group" style="--stage:' + stageVar( g.s.id ) + '"><h3 class="mmcl-book__stage">' + esc( g.s.name ) + '</h3>' +
					g.items.map( function ( e ) {
						return '<div class="mmcl-book__row">' +
							'<span class="mmcl-book__date">' + ( e.date ? esc( fmtDay( e.date ) ) : 'termin?' ) + '</span>' +
							'<span class="mmcl-book__title">' + esc( e.title ) + linkHtml( e ) + '</span>' +
							'<button type="button" class="mmcl-btn mmcl-btn--ghost mmcl-btn--small" data-act="booked" data-id="' + esc( e.id ) + '" data-fk="booked-' + esc( e.id ) + '">Gotowe<span class="mmcl-sr">, zarezerwowane: ' + esc( e.title ) + '</span></button>' +
						'</div>';
					} ).join( '' ) + '</div>';
			} ).join( '' ) + '</div>' : '<p class="mmcl-book__empty">Wszystko zarezerwowane.</p>';
			var doneHtml = done.length ? '<details class="mmcl-done-list"><summary>Zarezerwowane (' + done.length + ')</summary><ul>' + done.map( function ( e ) {
				return '<li>' + ( e.date ? esc( fmtShort( e.date ) ) + ' ' : '' ) + esc( e.title ) + ' <button type="button" class="mmcl-link-btn" data-act="unbook" data-id="' + esc( e.id ) + '" data-fk="unbook-' + esc( e.id ) + '">cofnij</button></li>';
			} ).join( '' ) + '</ul></details>' : '';
			return '<section class="mmcl-section" id="mmcl-book">' +
				sectionHead( 'Do zarezerwowania', 'Pendiente', todo.length ? todo.length + ' do załatwienia' : '' ) +
				body + doneHtml + '</section>';
		}

		function planHtml() {
			var list = events();
			var today = phase() === 'during' ? todayTrip() : '';
			var html = P.stages.map( function ( s ) {
				var days = [];
				for ( var d = s.from; d <= s.to; d = addDays( d, 1 ) ) {
					var on = eventsOn( d, list );
					var sleep = staysOverNight( d, list );
					days.push( '<div class="mmcl-day' + ( d === today ? ' is-today' : '' ) + '"' + ( d === today ? ' id="mmcl-today"' : '' ) + '>' +
						'<div class="mmcl-day__date"><span class="mmcl-day__n">Dzień ' + dayN( d ) + '</span><span class="mmcl-day__d">' + esc( fmtDay( d ) ) + '</span>' + ( d === today ? '<span class="mmcl-day__today">dziś</span>' : '' ) + '</div>' +
						'<div>' + ( on.length ? on.map( function ( e ) { return evRow( e ); } ).join( '' ) : ( sleep.length ? '' : '<div class="mmcl-day__empty">Wolne. Nic nie zaplanowane.</div>' ) ) +
						sleep.map( function ( e ) {
							var nm = e.title.replace( /^Nocleg(:| w| u)?\s*/i, '' );
							return '<div class="mmcl-day__sleep">' + icon( 'stay' ) + 'Nocleg: ' + esc( nm ) + ( e.place && nm.indexOf( e.place ) < 0 ? ', ' + esc( e.place ) : '' ) + '</div>';
						} ).join( '' ) +
						'</div></div>' );
				}
				var undated = list.filter( function ( e ) { return e.stage === s.id && ! e.date; } );
				if ( undated.length ) {
					days.push( '<div class="mmcl-day mmcl-day--tbc"><div class="mmcl-day__date"><span class="mmcl-day__d">Termin do ustalenia</span></div><div>' +
						undated.map( function ( e ) { return evRow( e ); } ).join( '' ) + '</div></div>' );
				}
				var range = s.from === s.to ? fmtDM( s.from ) : fmt( s.from, { day: 'numeric', month: 'long' } ) + ' – ' + fmtDM( s.to );
				return '<article class="mmcl-stage" id="mmcl-stage-' + esc( s.id ) + '" style="--stage:' + stageVar( s.id ) + '">' +
					'<div class="mmcl-stage__head">' + band( 'var(--stage)', 'rgba(255,255,255,.28)', 'rgba(0,0,0,.18)' ) +
						'<div class="mmcl-stage__bar"><h3 class="mmcl-stage__name">' + esc( s.name ) + '</h3><span class="mmcl-stage__dates">' + esc( range ) + '</span><span class="mmcl-stamp">' + esc( s.stamp ) + '</span></div>' +
					'</div>' +
					'<div class="mmcl-stage__body">' + days.join( '' ) + '</div>' +
				'</article>';
			} ).join( '' );
			return '<section class="mmcl-section" id="mmcl-plan">' + sectionHead( 'Plan dzień po dniu', 'Día a día', today ? '<a href="#mmcl-today">Przejdź do dzisiaj</a>' : '' ) + html + '</section>';
		}

		function passHtml( e, label ) {
			if ( ! e ) { return ''; }
			var codes = ( e.place.split( ',' )[ 0 ] || '' ).split( '→' ).map( function ( s ) { return s.trim(); } );
			var cities = e.title.split( '→' ).map( function ( s ) { return s.trim(); } );
			return '<div class="mmcl-pass" style="--stage:' + stageVar( e.stage ) + '">' +
				'<div class="mmcl-pass__main">' +
					'<div class="mmcl-pass__route"><span>' + esc( codes[ 0 ] ) + '</span>' + icon( 'flight' ) + '<span>' + esc( codes[ 1 ] || '' ) + '</span></div>' +
					'<div class="mmcl-pass__cities"><span>' + esc( cities[ 0 ] ) + '</span><span>' + esc( cities[ 1 ] || '' ) + '</span></div>' +
					'<div class="mmcl-pass__times">' +
						'<div><strong>' + esc( e.time || '—' ) + '</strong><span>' + ( e.date ? esc( fmtDay( e.date ) ) : 'termin?' ) + '</span></div>' +
						'<div><strong>' + esc( e.end_time || '—' ) + '</strong><span>' + ( e.end_date ? esc( fmtDay( e.end_date ) ) : '' ) + '</span></div>' +
					'</div>' +
					'<div class="mmcl-pass__meta">' + statusChips( e ) + '<span>' + esc( e.detail ) + '</span>' + editBtn( e ) + '</div>' +
					( PRIV && e.note ? '<div class="mmcl-ev__note">' + esc( e.note ) + '</div>' : '' ) +
				'</div>' +
				'<div class="mmcl-pass__stub"><div>' + esc( label ) + '<strong>Turkish Airlines</strong></div><div>1 przesiadka</div></div>' +
			'</div>';
		}

		function travelHtml() {
			var list = events();
			var out = list.filter( function ( e ) { return e.id === 'lot-tam'; } )[ 0 ];
			var back = list.filter( function ( e ) { return e.id === 'lot-powrot'; } )[ 0 ];
			var legs = list.filter( function ( e ) {
				return /flight|bus|transfer|car/.test( e.type ) && e.id !== 'lot-tam' && e.id !== 'lot-powrot';
			} ).sort( sortEv );
			var legsHtml = legs.map( function ( e ) {
				var when = e.date ? fmtDay( e.date ) + ( e.time ? ', ' + e.time : '' ) : 'termin?';
				return '<div class="mmcl-leg" style="--stage:' + stageVar( e.stage ) + '">' +
					'<div class="mmcl-leg__top"><span>' + esc( when ) + '</span><span>' + esc( STAGE_BY_ID[ e.stage ] ? STAGE_BY_ID[ e.stage ].name : '' ) + '</span></div>' +
					'<div class="mmcl-leg__title">' + icon( e.type ) + '<span>' + esc( e.title ) + '</span></div>' +
					( e.place ? '<div class="mmcl-leg__place">' + esc( e.place ) + '</div>' : '' ) +
					( PRIV && e.note ? '<div class="mmcl-ev__note">' + esc( e.note ) + '</div>' : '' ) +
					'<div class="mmcl-leg__foot"><span>' + statusChips( e ) + '</span>' + editBtn( e ) + '</div>' +
				'</div>';
			} ).join( '' );
			var bag = '<div class="mmcl-bag"><h3>Bagaż na lotach Turkish Airlines, na osobę</h3><table><thead><tr><th scope="col">Co</th><th scope="col">Ile</th><th scope="col">Wymiary</th><th scope="col">Waga</th></tr></thead><tbody>' +
				P.baggage.map( function ( b ) {
					return '<tr><td>' + esc( b.what ) + '</td><td class="num">' + b.n + '</td><td>' + esc( b.size ) + '</td><td class="num">' + esc( b.kg ) + '</td></tr>';
				} ).join( '' ) +
				'</tbody></table><p class="mmcl-bag__note">Bez pierwszeństwa wejścia na pokład. Loty krajowe w Chile mają zwykle mniejsze limity — pakujcie się pod najbardziej restrykcyjny.</p></div>';
			return '<section class="mmcl-section" id="mmcl-travel">' + sectionHead( 'Loty i przejazdy', 'Vuelos' ) +
				'<div class="mmcl-pass-row">' + passHtml( out, 'Wylot' ) + passHtml( back, 'Powrót' ) + '</div>' +
				'<div class="mmcl-legs">' + legsHtml + '</div>' + bag + '</section>';
		}

		/* ——— checklist ——— */
		function allItems() { return ( P.items || [] ).concat( S.state.custom || [] ); }
		function ticks( id ) { return ( S.state.ticks && S.state.ticks[ id ] ) || {}; }
		function isDone( it ) {
			var t = ticks( it.id );
			if ( it.scope === 'shared' || ! TRAV.length ) { return !! t.s || ( ! TRAV.length && Object.keys( t ).length > 0 ); }
			return TRAV.every( function ( tr ) { return !! t[ String( tr.id ) ]; } );
		}
		function nameOf( uid ) {
			var t = TRAV.filter( function ( x ) { return x.id === uid; } )[ 0 ];
			return t ? t.name : 'admin';
		}
		function listHtml() {
			if ( ! PRIV ) { return ''; }
			var items = allItems();
			var tabs = P.cats.map( function ( c ) {
				var mine = items.filter( function ( i ) { return i.cat === c.id; } );
				var done = mine.filter( isDone ).length;
				return '<button type="button" role="tab" class="mmcl-tab' + ( done === mine.length && mine.length ? ' is-complete' : '' ) + '" aria-selected="' + ( c.id === S.cat ) + '" aria-controls="mmcl-tabpanel" data-act="tab" data-cat="' + esc( c.id ) + '" data-fk="tab-' + esc( c.id ) + '">' +
					esc( c.name ) + '<span class="mmcl-tab__n">' + done + '/' + mine.length + '</span></button>';
			} ).join( '' );
			var cat = P.cats.filter( function ( c ) { return c.id === S.cat; } )[ 0 ];
			var mine = items.filter( function ( i ) { return i.cat === S.cat; } );
			var doneN = mine.filter( isDone ).length;
			var shown = S.hideDone ? mine.filter( function ( i ) { return ! isDone( i ); } ) : mine;
			var rows = shown.map( function ( it ) {
				var t = ticks( it.id );
				var btns;
				if ( it.scope === 'shared' || ! TRAV.length ) {
					var on = !! t.s;
					btns = '<button type="button" class="mmcl-tick mmcl-tick--solo" aria-pressed="' + on + '" data-act="tick" data-item="' + esc( it.id ) + '" data-who="s" data-fk="tick-' + esc( it.id ) + '-s">' + tickSvg() + '<span class="mmcl-sr">' + esc( it.label ) + '</span></button>';
				} else {
					btns = TRAV.map( function ( tr ) {
						var onp = !! t[ String( tr.id ) ];
						return '<button type="button" class="mmcl-tick" aria-pressed="' + onp + '" data-act="tick" data-item="' + esc( it.id ) + '" data-who="' + tr.id + '" data-fk="tick-' + esc( it.id ) + '-' + tr.id + '">' + tickSvg() + esc( tr.name ) + '<span class="mmcl-sr"> — ' + esc( it.label ) + '</span></button>';
					} ).join( '' );
				}
				var whoLine = '';
				if ( it.scope === 'shared' && t.s ) {
					whoLine = '<span class="mmcl-check__who">' + esc( nameOf( t.s.by ) ) + ', ' + esc( new Intl.DateTimeFormat( 'pl-PL', { day: 'numeric', month: 'short' } ).format( new Date( t.s.at * 1000 ) ) ) + '</span>';
				}
				var hint = it.hint ? '<span class="mmcl-check__hint">' + esc( it.hint ) + ( it.link ? ' <a href="' + esc( it.link ) + '" target="_blank" rel="noopener">strona</a>' : '' ) + '</span>' : ( it.link ? '<span class="mmcl-check__hint"><a href="' + esc( it.link ) + '" target="_blank" rel="noopener">strona</a></span>' : '' );
				return '<li class="mmcl-check' + ( isDone( it ) ? ' is-done' : '' ) + '">' +
					'<div class="mmcl-check__label"><span>' + esc( it.label ) + '</span>' + hint + whoLine + '</div>' +
					'<div class="mmcl-check__ticks">' + btns +
						( it.custom ? '<button type="button" class="mmcl-check__del" data-act="del" data-id="' + esc( it.id ) + '" aria-label="Usuń: ' + esc( it.label ) + '">×</button>' : '' ) +
					'</div></li>';
			} ).join( '' );
			var travNote = TRAV.length === 2 ? 'Punkty z imionami odhacza każde z Was osobno. Resztę wystarczy odhaczyć raz.' :
				'Dodaj dwoje podróżnych w ustawieniach, żeby osobne punkty (paszport, ubezpieczenie) miały imiona.';
			return '<section class="mmcl-section" id="mmcl-list">' + sectionHead( 'Lista', 'No olvidar' ) +
				'<div class="mmcl-tabs" role="tablist" aria-label="Kategorie listy">' + tabs + '</div>' +
				'<div id="mmcl-tabpanel" role="tabpanel" aria-label="' + esc( cat ? cat.name : '' ) + '">' +
					'<div class="mmcl-list__tools"><strong>' + esc( cat ? cat.name : '' ) + ': ' + doneN + ' z ' + mine.length + '</strong>' +
					'<label><input type="checkbox" data-act="hide-done" data-fk="hide-done"' + ( S.hideDone ? ' checked' : '' ) + '> Ukryj zrobione</label></div>' +
					'<div class="mmcl-progress" aria-hidden="true"><span style="width:' + ( mine.length ? Math.round( 100 * doneN / mine.length ) : 0 ) + '%"></span></div>' +
					'<ul>' + ( rows || '<li class="mmcl-empty">Wszystko z tej kategorii zrobione.</li>' ) + '</ul>' +
					'<form class="mmcl-add" data-act="add">' +
						'<label class="mmcl-sr" for="mmcl-add-label">Nowy punkt</label>' +
						'<input id="mmcl-add-label" type="text" name="label" maxlength="200" placeholder="Dodaj punkt do tej kategorii" data-fk="add-label">' +
						'<label class="mmcl-sr" for="mmcl-add-scope">Dla kogo</label>' +
						'<select id="mmcl-add-scope" name="scope"><option value="shared">Wspólny</option><option value="personal">Osobno dla każdego</option></select>' +
						'<button type="submit" class="mmcl-btn mmcl-btn--small">Dodaj</button>' +
					'</form>' +
					'<p class="mmcl-legend">' + esc( travNote ) + ' Zmiany widzicie oboje — panel odświeża się co minutę.</p>' +
				'</div></section>';
		}

		function infoHtml() {
			var f = P.facts;
			return '<section class="mmcl-section" id="mmcl-info">' + sectionHead( 'Na miejscu', 'En Chile' ) +
				'<div class="mmcl-info">' +
					'<div class="mmcl-info__card"><h3>Numery alarmowe</h3><div class="mmcl-sos">' +
						f.emergency.map( function ( n ) { return '<a href="tel:' + esc( n.n ) + '">' + esc( n.n ) + '</a><span>' + esc( n.what ) + '</span>'; } ).join( '' ) +
					'</div></div>' +
					'<div class="mmcl-info__card"><h3>' + esc( f.embassy.name ) + '</h3>' +
						'<p>' + esc( f.embassy.address ) + '</p>' +
						'<p>Telefon: <a href="tel:' + esc( f.embassy.phone.replace( /\s/g, '' ) ) + '">' + esc( f.embassy.phone ) + '</a><br>Dyżur konsularny w nagłych sprawach: <a href="tel:' + esc( f.embassy.duty.replace( /\s/g, '' ) ) + '">' + esc( f.embassy.duty ) + '</a></p>' +
						'<p><a href="' + esc( f.embassy.url ) + '" target="_blank" rel="noopener">Strona ambasady</a></p>' +
					'</div>' +
					'<div class="mmcl-info__card"><h3>Dobrze wiedzieć</h3>' +
						'<p>' + esc( f.time ) + '</p><p>' + esc( f.power ) + '</p><p>' + esc( f.tips ) + '</p><p>' + esc( f.holidays ) + '</p>' +
					'</div>' +
				'</div></section>';
		}

		function appsHtml() {
			var cols = [ 'copihue', 'araucaria', 'glaciar', 'terracota', 'pacifico', 'ochre' ];
			return '<section class="mmcl-section" id="mmcl-apps">' + sectionHead( 'Aplikacje i strony', 'Apps' ) +
				'<div class="mmcl-apps">' + P.apps.map( function ( a, i ) {
					return '<a class="mmcl-app" href="' + esc( a.url ) + '" target="_blank" rel="noopener" style="--c:var(--mmcl-' + cols[ i % cols.length ] + ')">' +
						'<span class="mmcl-app__mono" aria-hidden="true">' + esc( a.name.charAt( 0 ) ) + '</span>' +
						'<span class="mmcl-app__text"><strong>' + esc( a.name ) + '</strong><span class="mmcl-app__what">' + esc( a.what ) + '</span></span></a>';
				} ).join( '' ) + '</div></section>';
		}

		function contactsHtml() {
			if ( ! PRIV ) { return ''; }
			var c = P.contacts || [];
			var body = c.length ? '<div class="mmcl-contacts">' + c.map( function ( card ) {
				return '<div class="mmcl-contact"><h3>' + esc( card.title ) + '</h3><dl>' + card.rows.map( function ( r ) {
					var v = r.tel ? '<a href="tel:' + esc( String( r.v ).replace( /[^\d+]/g, '' ) ) + '">' + esc( r.v ) + '</a>' : esc( r.v );
					return ( r.k ? '<dt>' + esc( r.k ) + '</dt>' : '' ) + '<dd>' + v + '</dd>';
				} ).join( '' ) + '</dl></div>';
			} ).join( '' ) + '</div>' : '<p class="mmcl-empty">Adresy, telefony gospodarzy i przewodnika oraz numer polisy wpisz w ustawieniach wtyczki' + ( P.adminUrl ? ' (<a href="' + esc( P.adminUrl ) + '">Chile 2026 → Ustawienia</a>)' : '' ) + '. Widać je tylko po zalogowaniu.</p>';
			return '<section class="mmcl-section" id="mmcl-contacts">' + sectionHead( 'Kontakty', 'Contactos' ) + body + '</section>';
		}

		function footHtml() {
			return '<footer class="mmcl-foot">' + band( 'var(--mmcl-ink)', 'var(--mmcl-ochre)', 'var(--mmcl-copihue)' ) +
				'<div class="mmcl-foot__inner"><span>Plan przygotowany 25 października 2026. Fakty praktyczne sprawdzone tego dnia.</span>' +
				( P.adminUrl ? '<a href="' + esc( P.adminUrl ) + '">Ustawienia panelu</a>' : '' ) + '</div></footer>';
		}

		function chromeHtml() {
			var scales = Object.keys( P.scales ).map( function ( k ) {
				return '<button type="button" data-act="scale" data-scale="' + esc( k ) + '" aria-pressed="' + ( k === S.scale ) + '">' + esc( P.scales[ k ].label ) + '</button>';
			} ).join( '' );
			return '<button type="button" class="mmcl-gear" data-act="panel" aria-expanded="false" aria-controls="mmcl-panel">' + icon( 'gear' ) + 'Tekst</button>' +
				'<div class="mmcl-panel" id="mmcl-panel" hidden><h3>Wielkość tekstu</h3><div class="mmcl-panel__opts">' + scales + '</div>' +
				'<div class="mmcl-panel__preview"><strong>Torres del Paine</strong>Rejs do lodowca Grey, 3 stycznia.</div></div>' +
				'<div class="mmcl-toast" role="status" aria-live="polite" hidden></div>' +
				( PRIV ? dialogHtml() : '' );
		}

		function dialogHtml() {
			return '<dialog class="mmcl-dialog" aria-labelledby="mmcl-dlg-title"><form method="dialog" data-act="save-event">' +
				'<h3 id="mmcl-dlg-title"></h3><p class="mmcl-dialog__sub" data-dlg-sub></p>' +
				'<input type="hidden" name="id">' +
				'<div class="mmcl-dialog__field"><label for="mmcl-d-name">Nazwa</label><input id="mmcl-d-name" type="text" name="ev_title" maxlength="200">' +
				'<p class="mmcl-dialog__hint" data-dlg-orig></p></div>' +
				'<div class="row"><div><label for="mmcl-d-date">Data</label><input id="mmcl-d-date" type="date" name="date" min="' + TRIP.first_day + '" max="' + TRIP.last_day + '"></div>' +
				'<div><label for="mmcl-d-time">Godzina</label><input id="mmcl-d-time" type="time" name="time"></div></div>' +
				'<div class="row" data-dlg-end><div><label for="mmcl-d-edate" data-dlg-end-label>Koniec — data</label><input id="mmcl-d-edate" type="date" name="end_date" min="' + TRIP.first_day + '" max="' + TRIP.last_day + '"></div>' +
				'<div><label for="mmcl-d-etime">Godzina</label><input id="mmcl-d-etime" type="time" name="end_time"></div></div>' +
				'<fieldset data-dlg-status><legend>Status</legend>' +
					'<label><input type="radio" name="status" value="booked"> Zarezerwowane</label>' +
					'<label><input type="radio" name="status" value="todo"> Do zarezerwowania</label>' +
				'</fieldset>' +
				'<fieldset><legend>Oznaczenia</legend>' +
					'<label><input type="checkbox" name="ev_important"> Ważne</label>' +
					'<label data-dlg-done><input type="checkbox" name="ev_done"> Zrobione</label>' +
				'</fieldset>' +
				'<div><label for="mmcl-d-note">Notatka (numer rezerwacji, godzina zbiórki, kontakt)</label><textarea id="mmcl-d-note" name="note" maxlength="2000"></textarea>' +
				'<p class="mmcl-dialog__hint">Widzą ją tylko zalogowani podróżni. Nie wpisuj numerów kart ani paszportów.</p></div>' +
				'<div class="mmcl-dialog__actions"><button type="button" class="mmcl-btn mmcl-btn--ghost" data-act="close-dlg">Anuluj</button><button type="submit" class="mmcl-btn">Zapisz</button></div>' +
			'</form></dialog>';
		}

		/* ——— render ——— */

		var SECTIONS = {
			hero: heroHtml, nav: navHtml, next: nextHtml, book: bookHtml, plan: planHtml,
			travel: travelHtml, list: listHtml, info: infoHtml, apps: appsHtml, contacts: contactsHtml, foot: footHtml
		};
		var order = Object.keys( SECTIONS );
		var holder = document.createElement( 'div' );
		holder.className = 'mmcl-app-root';
		holder.innerHTML = order.map( function ( k ) { return '<div data-sec="' + k + '"></div>'; } ).join( '' ) + chromeHtml();
		root.appendChild( holder );
		root.style.setProperty( '--mmcl-scale', P.scales[ S.scale ] ? P.scales[ S.scale ].value : 1 );

		function render( keys ) {
			var active = document.activeElement;
			var fk = active && root.contains( active ) ? active.getAttribute( 'data-fk' ) : null;
			( keys || order ).forEach( function ( k ) {
				var el = holder.querySelector( '[data-sec="' + k + '"]' );
				if ( el ) { el.innerHTML = SECTIONS[ k ](); }
			} );
			if ( fk ) {
				var again = holder.querySelector( '[data-fk="' + fk + '"]' );
				if ( again ) { again.focus( { preventScroll: true } ); }
			}
		}
		var DYNAMIC = [ 'hero', 'nav', 'next', 'book', 'plan', 'travel', 'list' ];
		render();

		/* Live clocks: countdown every second, Santiago/Warsaw every 20 s. */
		var lastPhase = phase();
		setInterval( function () {
			var ph = phase();
			if ( ph !== lastPhase ) { lastPhase = ph; render( DYNAMIC ); return; }
			var c = holder.querySelector( '[data-count]' );
			if ( c && ph === 'before' ) { c.innerHTML = countHtml( ph ); }
		}, 1000 );
		setInterval( function () {
			var a = holder.querySelector( '[data-clock]' ), b = holder.querySelector( '[data-clock-waw]' );
			if ( a ) { a.textContent = timeIn( TRIP.chile_tz ); }
			if ( b ) { b.textContent = timeIn( 'Europe/Warsaw' ); }
		}, 20000 );

		/* ——— server ——— */

		var toastTimer;
		function toast( msg, isErr ) {
			var t = holder.querySelector( '.mmcl-toast' );
			t.textContent = msg;
			t.classList.toggle( 'is-error', !! isErr );
			t.hidden = false;
			clearTimeout( toastTimer );
			toastTimer = setTimeout( function () { t.hidden = true; }, isErr ? 6000 : 2500 );
		}
		function api( path, body ) {
			return fetch( P.rest.url + path, {
				method: body ? 'POST' : 'GET',
				credentials: 'same-origin',
				headers: { 'X-WP-Nonce': P.rest.nonce, 'Content-Type': 'application/json' },
				body: body ? JSON.stringify( body ) : undefined
			} ).then( function ( r ) {
				return r.json().catch( function () { return null; } ).then( function ( j ) {
					if ( ! r.ok ) {
						var msg = j && j.message ? j.message : 'Nie udało się zapisać. Sprawdź połączenie i spróbuj jeszcze raz.';
						if ( r.status === 403 ) { msg = 'Sesja wygasła. Odśwież stronę i zaloguj się ponownie.'; }
						throw new Error( msg );
					}
					return j;
				} );
			} );
		}
		function apply( state ) {
			if ( ! state ) { return; }
			S.state = {
				ticks: state.ticks && ! Array.isArray( state.ticks ) ? state.ticks : {},
				overrides: state.overrides && ! Array.isArray( state.overrides ) ? state.overrides : {},
				custom: state.custom || []
			};
			render( DYNAMIC );
		}
		function send( path, body, okMsg, rollback ) {
			return api( path, body ).then( function ( st ) {
				apply( st );
				if ( okMsg ) { toast( okMsg ); }
				return true;
			} ).catch( function ( err ) {
				if ( rollback ) { rollback(); render( DYNAMIC ); }
				toast( err.message, true );
				return false;
			} );
		}

		if ( PRIV ) {
			setInterval( function () {
				var dlg = holder.querySelector( 'dialog' );
				if ( document.visibilityState !== 'visible' || ( dlg && dlg.open ) ) { return; }
				var focused = document.activeElement;
				if ( focused && root.contains( focused ) && /INPUT|TEXTAREA|SELECT/.test( focused.tagName ) ) { return; }
				api( 'state' ).then( function ( st ) {
					if ( JSON.stringify( st ) !== JSON.stringify( S.state ) ) { apply( st ); }
				} ).catch( function () { /* next round */ } );
			}, 60000 );
		}

		/* ——— interactions ——— */

		function openEdit( id ) {
			var e = evById( id );
			var dlg = holder.querySelector( 'dialog' );
			if ( ! e || ! dlg ) { return; }
			var f = dlg.querySelector( 'form' );
			dlg.querySelector( '#mmcl-dlg-title' ).textContent = e.title;
			dlg.querySelector( '[data-dlg-sub]' ).textContent = [ STAGE_BY_ID[ e.stage ] ? STAGE_BY_ID[ e.stage ].name : '', e.place ].filter( Boolean ).join( ', ' );
			f.id.value = e.id;
			f.date.value = e.date || '';
			f.time.value = e.time || '';
			var hasEnd = /flight|bus|transfer|stay|car/.test( e.type );
			dlg.querySelector( '[data-dlg-end]' ).hidden = ! hasEnd;
			dlg.querySelector( '[data-dlg-end-label]' ).textContent = e.type === 'stay' ? 'Wymeldowanie — data' : e.type === 'car' ? 'Zwrot — data' : 'Przylot / przyjazd — data';
			f.end_date.value = e.end_date || '';
			f.end_time.value = e.end_time || '';
			var stBox = dlg.querySelector( '[data-dlg-status]' );
			stBox.hidden = ! e.booking;
			Array.prototype.forEach.call( f.querySelectorAll( 'input[name=status]' ), function ( r ) { r.checked = r.value === e.status; } );
			f.note.value = e.note || '';
			f.elements.ev_title.value = e.title;
			f.elements.ev_title.setAttribute( 'data-orig', e.origTitle );
			dlg.querySelector( '[data-dlg-orig]' ).textContent = e.title !== e.origTitle ? 'Pierwotnie: ' + e.origTitle + '. Wyczyść pole, żeby przywrócić.' : '';
			f.elements.ev_important.checked = !! e.important;
			f.elements.ev_done.checked = !! e.done;
			dlg.querySelector( '[data-dlg-done]' ).hidden = e.type !== 'task';
			dlg.showModal();
		}

		holder.addEventListener( 'click', function ( ev ) {
			var el = ev.target.closest( '[data-act]' );
			if ( ! el || ! holder.contains( el ) ) { return; }
			var act = el.getAttribute( 'data-act' );

			if ( act === 'goto-stage' ) {
				var target = holder.querySelector( '#mmcl-stage-' + el.getAttribute( 'data-stage' ) );
				if ( target ) { target.scrollIntoView( { behavior: matchMedia( '(prefers-reduced-motion: reduce)' ).matches ? 'auto' : 'smooth' } ); }
				return;
			}
			if ( act === 'panel' ) {
				var panel = holder.querySelector( '.mmcl-panel' );
				panel.hidden = ! panel.hidden;
				el.setAttribute( 'aria-expanded', String( ! panel.hidden ) );
				return;
			}
			if ( act === 'scale' ) {
				S.scale = el.getAttribute( 'data-scale' );
				store( 'scale', S.scale );
				root.style.setProperty( '--mmcl-scale', P.scales[ S.scale ].value );
				Array.prototype.forEach.call( holder.querySelectorAll( '[data-act=scale]' ), function ( b ) {
					b.setAttribute( 'aria-pressed', String( b === el ) );
				} );
				return;
			}
			if ( act === 'tab' ) {
				S.cat = el.getAttribute( 'data-cat' );
				store( 'cat', S.cat );
				render( [ 'list' ] );
				return;
			}
			if ( act === 'tick' ) {
				var item = el.getAttribute( 'data-item' ), who = el.getAttribute( 'data-who' );
				var done = el.getAttribute( 'aria-pressed' ) !== 'true';
				var prev = JSON.stringify( S.state.ticks );
				var key = who === 's' ? 's' : String( who );
				S.state.ticks[ item ] = S.state.ticks[ item ] || {};
				if ( done ) { S.state.ticks[ item ][ key ] = { by: P.me, at: Math.floor( Date.now() / 1000 ) }; } else { delete S.state.ticks[ item ][ key ]; }
				render( [ 'list' ] );
				send( 'tick', { item: item, who: who, done: done }, '', function () { S.state.ticks = JSON.parse( prev ); } );
				return;
			}
			if ( act === 'del' ) {
				if ( ! window.confirm( 'Usunąć ten punkt z listy?' ) ) { return; }
				send( 'custom', { op: 'delete', id: el.getAttribute( 'data-id' ) }, 'Usunięto punkt.' );
				return;
			}
			if ( act === 'booked' || act === 'unbook' ) {
				var id = el.getAttribute( 'data-id' );
				var status = act === 'booked' ? 'booked' : 'todo';
				send( 'event', { id: id, status: status }, act === 'booked' ? 'Oznaczono jako zarezerwowane.' : 'Wróciło na listę do zarezerwowania.' );
				return;
			}
			if ( act === 'edit' ) {
				openEdit( el.getAttribute( 'data-id' ) );
				return;
			}
			if ( act === 'task-done' ) {
				var tid = el.getAttribute( 'data-id' );
				var nowDone = el.getAttribute( 'aria-pressed' ) !== 'true';
				var before = JSON.stringify( S.state.overrides );
				S.state.overrides[ tid ] = Object.assign( {}, S.state.overrides[ tid ], { done: nowDone } );
				render( DYNAMIC );
				send( 'event', { id: tid, done: nowDone }, nowDone ? 'Zrobione.' : 'Cofnięto.', function () { S.state.overrides = JSON.parse( before ); } );
				return;
			}
			if ( act === 'close-dlg' ) {
				holder.querySelector( 'dialog' ).close();
			}
		} );

		holder.addEventListener( 'keydown', function ( ev ) {
			var el = ev.target.closest && ev.target.closest( '[data-act="goto-stage"]' );
			if ( el && ( ev.key === 'Enter' || ev.key === ' ' ) ) {
				ev.preventDefault();
				el.dispatchEvent( new MouseEvent( 'click', { bubbles: true } ) );
			}
		} );

		holder.addEventListener( 'change', function ( ev ) {
			var el = ev.target;
			if ( el.getAttribute( 'data-act' ) === 'hide-done' ) {
				S.hideDone = el.checked;
				store( 'hideDone', el.checked ? '1' : '0' );
				render( [ 'list' ] );
			}
		} );

		holder.addEventListener( 'input', function ( ev ) {
			var el = ev.target;
			var which = el.getAttribute( 'data-fx' );
			if ( ! which ) { return; }
			var v = parseFloat( String( el.value ).replace( /\s/g, '' ).replace( ',', '.' ) );
			var other = holder.querySelector( '[data-fx="' + ( which === 'pln' ? 'clp' : 'pln' ) + '"]' );
			if ( isNaN( v ) ) { other.value = ''; return; }
			other.value = which === 'pln' ? Math.round( v * P.fx ) : ( Math.round( v / P.fx * 100 ) / 100 ).toString().replace( '.', ',' );
		} );

		holder.addEventListener( 'submit', function ( ev ) {
			var f = ev.target;
			var act = f.getAttribute( 'data-act' );
			if ( act === 'add' ) {
				ev.preventDefault();
				var label = f.label.value.trim();
				if ( ! label ) { toast( 'Wpisz treść punktu.', true ); f.label.focus(); return; }
				var scope = f.scope.value;
				f.label.value = '';
				send( 'custom', { op: 'add', cat: S.cat, label: label, scope: scope }, 'Dodano punkt.' );
				return;
			}
			if ( act === 'save-event' ) {
				ev.preventDefault();
				var body = {
					id: f.id.value,
					date: f.date.value,
					time: f.time.value,
					note: f.note.value
				};
				if ( ! holder.querySelector( '[data-dlg-end]' ).hidden ) {
					body.end_date = f.end_date.value;
					body.end_time = f.end_time.value;
				}
				var checked = f.querySelector( 'input[name=status]:checked' );
				if ( checked && ! holder.querySelector( '[data-dlg-status]' ).hidden ) { body.status = checked.value; }
				var nameIn = f.elements.ev_title;
				var newName = nameIn.value.trim();
				body.title = newName === nameIn.getAttribute( 'data-orig' ) ? '' : newName;
				body.important = f.elements.ev_important.checked;
				if ( ! holder.querySelector( '[data-dlg-done]' ).hidden ) { body.done = f.elements.ev_done.checked; }
				send( 'event', body, 'Zapisano.' ).then( function ( ok ) {
					var dlg = holder.querySelector( 'dialog' );
					if ( ok && dlg.open ) { dlg.close(); }
				} );
			}
		} );
	}
}() );
