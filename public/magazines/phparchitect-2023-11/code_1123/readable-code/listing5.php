class Article {
    private $title;
    private $content;

    public function __construct($title, $content) {
        $this->title = $title;
        $this->content = $content;
    }

    public function getTitle() {
        return $this->title;
    }

    public function getContent() {
        return $this->content;
    }
}

class ArticleRepository {
    private $articles = [];

    public function addArticle(Article $article) {
        $this->articles[] = $article;
    }

    public function findArticleByTitle($title) {
        foreach ($this->articles as $article) {
            if ($article->getTitle() === $title) {
                return $article;
            }
        }
        return null;
    }

    public function deleteArticle(Article $article) {
        $articles = $this->articles;
        foreach ($articles as $key => $storedArticle) {
            if ($storedArticle === $article) {
                unset($this->articles[$key]);
            }
        }
    }
}

$articleRepository = new ArticleRepository();
$article1 = new Article('First Article',
    'This is the content of the first article.');
$article2 = new Article('Second Article',
    'Content of the second article.');

$articleRepository->addArticle($article1);
$articleRepository->addArticle($article2);

$foundArticle = $articleRepository
            ->findArticleByTitle('First Article');
if ($foundArticle) {
    echo "Found article: {$foundArticle->getTitle()}";
}

$articleRepository->deleteArticle($article1);