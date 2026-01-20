/* https://dev.to/arnu515/auto-dark-light-theme-using-css-only-2e0m */
:root {
  	overscroll-behavior: none; /* https://css-irl.info/preventing-overscroll-bounce-with-css/ */
  	--nav-height: 4em;
  	--side-width: 30vw;

	--text: #333;
	--bg: #fff;
	
	/* input */
	--input-border: #BBB;
	--input-bg: #fff;
	--input-color: var(--text);
	
	--input-bg-focus: white;
	--input-border-focus: black;
	--input-focus-color: black;
}

@media (prefers-color-scheme: dark) {

  :root {
    --text: #d0d0d0;
    --bg: #121212;  /* https://m2.material.io/design/color/dark-theme.html#properties */
     
    --panel-bg: #222;
    --panel-shadow: black;
    
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

  }
}
