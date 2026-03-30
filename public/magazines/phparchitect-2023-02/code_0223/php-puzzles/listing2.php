// letter grade frequency
rsort($grades); 

// so our histogram plots A's first
//  Use array map to convert each int to a letter grade
$letters = array_map(function($grade) {    
	switch (true) {        
		case ($grade >= 90): return 'A';        
		case ($grade >= 80): return 'B';        
		case ($grade >= 70): return 'C';        
		case ($grade >= 60): return 'D';        
		default: return 'F';    
	}
}, $grades);

// now count the frequency of each
$letterFreq = array_count_values($letters);

// output a basic histogram
echo PHP_EOL . "Histogram of Grades" . PHP_EOL;
foreach ($letterFreq as $letter => $count) {    
	echo "{$letter} | " . str_repeat('█', $count)        
	. " ({$count})" . PHP_EOL;
}