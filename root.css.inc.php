/* https://dev.to/arnu515/auto-dark-light-theme-using-css-only-2e0m */
:root {
  	overscroll-behavior: none; /* https://css-irl.info/preventing-overscroll-bounce-with-css/ */
  	--nav-height: 4em;
  	--side-width: 30vw;

	--text: #333;
	--bg: #fff;

	/* borders */
	--border-color: #CCC;
	--border-color-dark: #ddd;

	/* buttons */
	--button-bg: #3300CC;
	--button-text: white;
	--button-border: #3300CC;

	/* status colors */
	--error-bg: #cc3333;
	--error-text: white;
	--warning-bg: #ff8c00;
	--warning-text: white;
	--unranked-bg: orange;

	/* citation output */
	--citation-bg: #f5f5f5;
	--citation-border: #ddd;

	/* dt color */
	--dt-color: #444;

	/* input */
	--input-border: #BBB;
	--input-bg: #fff;
	--input-color: var(--text);

	--input-bg-focus: white;
	--input-border-focus: black;
	--input-focus-color: black;
	
	--type-bg: var(--input-bg);
	
	--link-bg: green;
	--link-text: blue;
	
	--cell-bg: #eeeeee;
	--cell-border: rgb(200,200,200);
	--cell-hover: rgb(192,192,192);

}

@media (prefers-color-scheme: dark) {

  :root {
    --text: #d0d0d0;
    --bg: #121212;  /* https://m2.material.io/design/color/dark-theme.html#properties */

    --panel-bg: #222;
    --panel-shadow: black;

    /* borders */
    --border-color: #444;
    --border-color-dark: #555;

    /* buttons */
    --button-bg: #5533ff;
    --button-text: white;
    --button-border: #5533ff;

    /* status colors */
    --error-bg: #cc3333;
    --error-text: white;
    --warning-bg: #cc7700;
    --warning-text: white;
    --unranked-bg: #cc7700;

    /* citation output */
    --citation-bg: #1e1e1e;
    --citation-border: #444;

    /* dt color */
    --dt-color: #888;

    /* Make titles less heavy */
    h1 { font-weight: normal; }
    h2 { font-weight: normal; }

    /* change links */
    a { color: #76D6FF; }

   	/* input */
   	--input-border:var(--bg);
   	--input-bg: rgb(56,45,71);
	--input-color: rgb(212,180,250);

	--input-bg-focus: rgb(79,70,93);
	--input-border-focus: white;
	--input-focus-color: white;
	
	--cell-bg: blue;
	--cell-border: rgb(200,200,200);
	--cell-hover: rgb(192,192,192);
	


  }
}
