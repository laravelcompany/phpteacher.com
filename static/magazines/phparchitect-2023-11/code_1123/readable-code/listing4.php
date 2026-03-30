class Article {
    public $title;
    public $content;

    public function __construct($title, $content) {
        $this->title = $title;
        $this->content = $content;
    }
}

class ArticleManager {
    private $articles = [];

    public function addArticle($title, $content) {
        $article = new Article($title, $content);
        $this->articles[] = $article;
    }

    public function findArticleByTitle($title) {
        foreach ($this->articles as $article) {
            if ($article->title === $title) {
                return $article;
            }
        }
        return null;
    }

    public function deleteArticle($title) {
        foreach ($this->articles as $key => $article) {
            if ($article->title === $title) {
                unset($this->articles[$key]);
            }
        }
    }
}

$articleManager = new ArticleManager();
$articleManager->addArticle('First Article',
    'This is the content of the first article.');
$articleManager->addArticle('Second Article',
    'Content of the second article.');

$foundArticle = $articleManager->findArticleByTitle(
                        'First Article');
if ($foundArticle) {
    echo "Found article: {$foundArticle->title}";
}

$articleManager->deleteArticle('First Article');