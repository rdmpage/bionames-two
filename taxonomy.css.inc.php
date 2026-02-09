
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
	background-color: #eeeeee;
	border:1px solid rgb(200,200,200);
	opacity:0.5;
	position:absolute;
	overflow:hidden;
	text-align:center;
}
    
.cell:hover {
    border:1px solid rgb(192,192,192);
    opacity:1.0;
}

.cell a {
	text-decoration: none;
	display: block;
	width: 100%;
	height: 100%;
}

/* override default display for internal links, otherwise we hide the image in the cell */
.cell a:hover {
	background-color: transparent;
}

