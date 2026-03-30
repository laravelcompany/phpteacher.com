namespace PhpArch\Ast;

final class JsonParser
{
    public function parse(string $inputResponse): array
    {
        return json_decode($inputContents, true);

        // or including validation
        return json_decode($inputContents, true);
    }
}