
li.closed {
  list-style: none;         /* remove default bullet */
  position: relative;
  padding-left: 1.2em;      /* space reserved for marker */
}

li.closed::before {
  content: "◯";
  position: absolute;
  left: 0;
  top: 0.2em;               /* tweak vertical alignment */
}

li.open {
  list-style: none;         /* remove default bullet */
  position: relative;
  padding-left: 1.2em;      /* space reserved for marker */
}

li.open::before {
  content: "●";
  position: absolute;
  left: 0;
  top: 0.2em;               /* tweak vertical alignment */
}

/* Grid view for long lists of works (journal article listings) */
.works-grid {
	display: flex;
	flex-wrap: wrap;
	gap: 0.5em;
	margin-top: 0.5em;
}

.works-grid .works-year-tile {
	flex: 0 0 auto;
	width: 80px;
	height: 110px;
	display: flex;
	align-items: center;
	justify-content: center;
	background: var(--cell-bg);
	color: var(--text);
	border: 1px solid var(--cell-border);
	border-radius: 2px;
	font-weight: bold;
	font-size: 0.9em;
}

.works-grid .work-tile {
	flex: 0 0 auto;
	width: 80px;
	height: 110px;
	position: relative;
	display: block;
	background: var(--bg);
	border: 1px solid var(--border-color);
	border-radius: 2px;
	overflow: hidden;
	color: inherit;
}

.works-grid .work-tile:hover {
	border-color: var(--text);
	background: var(--bg);
}

.works-grid .work-tile-thumb {
	width: 100%;
	height: 100%;
	object-fit: cover;
	object-position: 50% 0;
	display: block;
}

.works-grid .work-tile-glyph {
	width: 32px;
	height: 32px;
	position: absolute;
	bottom: 0.5em;
	left: 50%;
	transform: translateX(-50%);
	opacity: 0.7;
	filter: var(--silhouette-filter);
}

.works-grid .work-tile-title {
	position: absolute;
	top: 0;
	left: 0;
	right: 0;
	padding: 0.25em 0.4em;
	background: rgba(0, 0, 0, 0.55);
	color: #fff;
	font-size: 0.7em;
	line-height: 1.15em;
	max-height: 60%;
	overflow: hidden;
	z-index: 1;
}

/* Plain tiles (no thumbnail / DOI) let the title fill the whole tile */
.works-grid .work-tile-plain {
	background: var(--cell-bg);
}

.works-grid .work-tile-plain .work-tile-title {
	background: transparent;
	color: var(--text);
	max-height: 100%;
	height: 100%;
}

