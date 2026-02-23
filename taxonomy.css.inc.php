
.family {
	font-variant: small-caps;
}
.subfamily {
	font-variant: small-caps;
}
.genericName {
	font-style: italic;
}
.infragenericEpithet {
	font-style: italic;
}
.specificEpithet {
	font-style: italic;
}
.infraspecificEpithet {
	font-style: italic;
}
.unranked {
	background-color:orange;
}

#treemap {
  --w: 1200;
  --h: 800;

  position: relative;
  width: 100%;
  aspect-ratio: calc(var(--w) / var(--h));
  
  display:none;
}

/* cell in treemap */
.cell {
	background-color: var(--cell-bg);
	border:1px solid var(--cell-border);
	opacity:0.5;
	position:absolute;
	overflow:hidden;
	text-align:center;
}

.cell:hover {
    border:1px solid var(--cell-hover);
    opacity:1.0;
}

.cell a {
	text-decoration: none;
	display: block;
	width: 100%;
	height: 100%;
	position: relative;
}

/* override default display for internal links, otherwise we hide the image in the cell */
.cell a:hover {
	background-color: transparent;
}

/* silhouette image inside treemap cell */
.cell a img.silhouette {
	position: absolute;
	top: 50%;
	left: 50%;
	transform: translate(-50%, -50%);
	width: 50%;
	height: 50%;
	object-fit: contain;
}

/* silhouette images - colour adapts to light/dark mode */
img.silhouette {
	opacity: 0.6;
	filter: var(--silhouette-filter);
}





