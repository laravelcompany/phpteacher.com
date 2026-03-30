<?php

class ProductTitlePrefixFilterIterator extends FilterIterator
{
    private string $prefix;

    public function __construct(Iterator $iterator, string $prefix)
    {
        parent::__construct($iterator);
        $this->prefix = $prefix;
    }

    public function accept() : bool
    {
        return str_starts_with(
                                parent::current()->getProductTitle(),
                                $this->prefix);
    }
}