<form method="post" action="/">
    <label for="textarea">
        Type your question:
    </label><br>
    <textarea id="textarea" name="question"
                    rows="4" cols="50"></textarea><br>
    <input type="submit" value="Submit">
</form>
<?php
require_once __DIR__ . '/vendor/autoload.php';
if (isset($_POST['question'])) {
    $question = $_POST['question'];
    $answer = generate_answer_with_gpt($question);
    echo "<h1>Your question was: $question <br></h1>";
    echo PHP_EOL;
    echo "<h2>The answer is: $answer</h2>";
}

function generate_answer_with_gpt(
    string $question
): string
{
    $client = OpenAI::client('sk-bZVLyQZEoCMxOy1h...');
    $response = $client->completions()->create([
        'model' => 'text-davinci-003',
        'prompt' => $question
    ]);
    return $response->choices[0]->text;
}