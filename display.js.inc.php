function display_pdf(pdf_url) {		
	var page = 1;
	var page_to_display = page || 1;

	var pdfjs_url 	= 'pdfjs/web/viewer.html?file=';

	var proxy_url 	= '../../pdfproxy.php?url=';

	var html = '<p><a href="' + pdf_url + '" target="_new">PDF</a></p>'
		+ '<iframe id="pdf" width="100%" height="700" frameBorder="0" src="' 
		+ pdfjs_url
		+ encodeURIComponent(proxy_url + encodeURIComponent(pdf_url))				
		+ '#page=' + page_to_display + '"/>';

	var output = document.getElementById("pdf");
	output.style.display = "none";
	output.innerHTML = html;
	output.style.display = "block";
}
