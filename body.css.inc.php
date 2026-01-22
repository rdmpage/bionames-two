
body {
	padding:0;
	margin:0;
	font-family: ui-sans-serif, system-ui, sans-serif;
 	background-color: var(--bg);
 	color: var(--text);  	
}

p {
	font-size:0.8em;
}

li {
	padding:0.3em;
}

/* based on https://bloycey.medium.com/how-to-style-dl-dt-and-dd-html-tags-ced1229deda0 */
dl {
  display: grid;
  grid-gap: 4px 16px;
  grid-template-columns: max-content;
}
dt {
  text-align:right;
  color:#444;
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

	background:#3300CC;
	color:white;
	border:1px solid #3300CC;
	
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
	background-color:red;
	color: white;
}

.warning {
	padding:1em;
	background-color:orange;
	color: white;
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
