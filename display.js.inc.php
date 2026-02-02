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

// Render citation from CSL JSON
function display_citation(cslJson, format) {
	try {
		var cite = new Cite(cslJson);

		switch(format) {
			case 'apa':
				return cite.format('bibliography', {
					format: 'html',
					template: 'apa',
					lang: 'en-US'
				});

			case 'bibtex':
				return '<pre>' + cite.format('bibtex') + '</pre>';

			case 'ris':
				return '<pre>' + cite.format('ris') + '</pre>';

			default:
				return cite.format('bibliography', {
					format: 'html',
					template: 'apa',
					lang: 'en-US'
				});
		}
	} catch (e) {
		return 'Error rendering citation: ' + e.message;
	}
}

// Show formatted citation from encoding
function show_citation(cslJsonText, format) {
	var outputDiv = document.getElementById('citation-output');
	if (!outputDiv) return;

	try {
		var cslJson = JSON.parse(cslJsonText);
		var formatted = display_citation(cslJson, format);
		outputDiv.innerHTML = formatted;
		outputDiv.style.display = 'block';
	} catch (e) {
		outputDiv.innerHTML = 'Error: ' + e.message;
		outputDiv.style.display = 'block';
	}
}

function drawTreemap(path) {
	var url = 'api.php?path=' + encodeURIComponent(path);
	
	fetch(url).then(
		function(response){
			if (response.status != 200) {
				console.log("Looks like there was a problem. Status Code: " + response.status);
				return;
			}
	
			response.json().then(function(data) {
				var html = '';
				for (var i in data) {
					html += '<div class="cell"'
						+ ' style="' 
						/*
						+ 'top:' + data[i].bounds.y + 'px;'
						+ 'left:' + data[i].bounds.x + 'px;'
						+ 'width:' + data[i].bounds.w + 'px;'
						+ 'height:' + data[i].bounds.h + 'px;'
						*/
						+ 'top:calc(' + data[i].bounds.y + ' / var(--h) * 100%);'
						+ 'left:calc(' + data[i].bounds.x + ' / var(--w) * 100%);'
						+ 'width:calc(' + data[i].bounds.w + ' / var(--w) * 100%);'
						+ 'height:calc(' + data[i].bounds.h + ' / var(--h) * 100%);'
						
					var image_url = 'images/' + data[i].label + '.svg';
					
					html += 'background-image:url(\'' + image_url + '\');';
					html += 'background-repeat:no-repeat;';
					html += 'background-position:center;';
					html += 'background-size: 50%;';
													
					html += '"';
					html += '>';
					
					html += '<a href="?path=' + encodeURIComponent(data[i].id) + '">';
						
					html += '<span>' + data[i].label + '</span>';
					
					html += '</a>';	
					
					html += '</div>';		
				
				
					document.getElementById("treemap").innerHTML = html;
				}
			});
		});

}



