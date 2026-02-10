
body {
	padding:0;
	margin:0;
	font-family: ui-sans-serif, system-ui, sans-serif;
 	background-color: var(--bg);
 	color: var(--text); 
 	font-size:1em;
}

/* main column */
main {
	/*width:90vw;
	padding:1em;*/
	padding:0px;
	width100vw;
	margin:auto;
}

.headline {
	width:90vw;
	margin:auto;
	margin-bottom: 1em;
	padding-top:1em;
	
	/* border:1px solid #CCC; */
}

.relationships {
	width:90vw;
	margin:auto;
	
	/* border:1px solid #CCC; */
}

.type {
	padding:0.2em;
	border: 1px solid #AAA;
	border-radius: 4px;
	display:inline;
	font-size:0.8em;
	background-color:var(--type-bg); 
}


h1, h2 {
	font-weight: normal;
	color:var(--text);
}

h1 {
	font-size:1.5em;
}

h2 {
	font-size:1.2em;
}

p {
}

li {
	padding:0.2em;
}

a {
    text-decoration: none;
    color: var(--link-text);
}

/* If we want an underline when we mouse over the link */
a:hover {
	background-color:var(--link-bg);   
}	

a.external:hover {
	text-decoration:underline;
	background-color:var(--link-bg);   
}	


/* based on https://steemit.com/unicode/@markgritter/why-did-unicode-reject-the-external-link-symbol */
a.external:after {
    content: '';
    background: url('data:image/svg+xml; utf8, <svg height="1024" width="768" xmlns="http://www.w3.org/2000/svg"><path d="M640 768H128V257.90599999999995L256 256V128H0v768h768V576H640V768zM384 128l128 128L320 448l128 128 192-192 128 128V128H384z"/></svg>');
    background-size: cover;
    display: inline-block;
    width: 0.6em;
    height: 0.8em;
    top: 0.05em;
    position: relative;
    left: 0.2em;
    margin-right: 0.2em;
    opacity: .8;
}

/* based on https://bloycey.medium.com/how-to-style-dl-dt-and-dd-html-tags-ced1229deda0 */
dl {
  display: grid;
  grid-gap: 4px 16px;
  grid-template-columns: max-content;
}
dt {
  text-align:right;
  color: var(--dt-color);
}
dd {
  margin: 0;
  grid-column-start: 2;
  /* font-weight: bold; */
}  

.search {
	border:1px solid var(--input-border);
 	background-color: var(--input-bg);
 	color: var(--input-color);
}  

.search:focus { 
	background-color: var(--input-bg-focus);
	border:1px solid var(--input-border-focus);
	color: var(--input-focus-color);
}


button {
	font-size:1em;

	background: var(--button-bg);
	color: var(--button-text);
	border: 1px solid var(--button-border);

	padding: 0.5em 1em;
	border-radius: 0.2em;

	-webkit-appearance: none;
	display: inline-block;
}

.spacer {
	display:block;
	height:1em;
}

.error {
	padding:1em;
	background-color: var(--error-bg);
	color: var(--error-text);
}

.warning {
	padding:1em;
	background-color: var(--warning-bg);
	color: var(--warning-text);
}

.multicolumn ul {
	columns: 200px;
	list-style: none;
	font-size:0.8em;
}
.multicolumn li {
	width:200px;
	white-space: nowrap; 
    overflow: hidden;
    text-overflow: ellipsis;
    line-height:1.2em;
    padding:0px;
}	

#pdf {
	border-top:1px solid #CCC;
}

.citation {
	font-size:0.8em;
}
