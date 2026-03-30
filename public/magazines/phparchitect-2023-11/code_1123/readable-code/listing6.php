interface Content {
    public function getTitle(): string;
    public function getContent(): string;
}

class Article implements Content {
    private $title;
    private $content;

    public function __construct($title, $content) {
        $this->title = $title;
        $this->content = $content;
    }

    public function getTitle(): string {
        return $this->title;
    }

    public function getContent(): string {
        return $this->content;
    }
}

class Video implements Content {
    private $title;
    private $url;

    public function __construct($title, $url) {
        $this->title = $title;
        $this->url = $url;
    }

    public function getTitle(): string {
        return $this->title;
    }

    public function getContent(): string {
        return "<iframe src='{$this->url}'></iframe>";
    }
}

interface ContentRepository {
    public function addContent(Content $content);
    public function findContentByTitle(
        string $title
    ): ?Content;
    public function deleteContent(Content $content);
}

class ContentRepositoryImpl
                    implements ContentRepository {
    private $contents = [];

    public function addContent(Content $content) {
        $this->contents[] = $content;
    }

    public function findContentByTitle(
        string $title
    ): ?Content {
        foreach ($this->contents as $content) {
            if ($content->getTitle() === $title) {
                return $content;
            }
        }
        return null;
    }

    public function deleteContent(Content $content) {
        $contents = $this->contents;
        foreach ($contents as $key => $storedContent) {
            if ($storedContent === $content) {
                unset($this->contents[$key]);
            }
        }
    }
}

$repository = new ContentRepositoryImpl();

$article = new Article('First Article',
    'This is the content of the first article.');
$video = new Video('Intro Video',
    'https://www.youtube.com/embed/12345');

$repository->addContent($article);
$repository->addContent($video);

$foundContent = $repository->findContentByTitle(
    'Intro Video'
);
if ($foundContent) {
    echo "Found: {$foundContent->getTitle()}\n";
    echo "Display:\n{$foundContent->getContent()}";
}

$repository->deleteContent($article);