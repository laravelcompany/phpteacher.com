$(() => {
	$("code").each((i, elt) => {
	  const languageClass = $(elt).attr("class")
	    .split(/\s+/)
	    .find(clss => clss.match(/^language-/));
	  if (!languageClass) {
	    return;
	  }
	
	  const language = languageClass.match(/^language-(.+)$/)[1];
	  const graphDefinition = $(elt).text().trim();
	
	  $.ajax({
	    type: "POST",
	    url: `https://kroki.io/${language}/svg`,
	    data: graphDefinition,
	    processData: false,
	    contentType: "text/plain",
	    success: (svgDocument) => {
	      const svg = svgDocument.documentElement.outerHTML;
	      const $pre = $(elt).parent();
	      $(svg).insertBefore($pre);
	      $pre.remove();
	    },
	  });
	});
});