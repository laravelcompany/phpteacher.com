$childAwareItemNode = new ChildAwareItemNode("key", [
    new ItemNode(0, "first-value"),
    new ItemNode(1, "second-value"),
]);

foreach ($childAwareItemNode->subNodes as $subItemNode)
{
    var_dump($subItemNode->value);
    // first "first-value"
    // then "second-value"
}