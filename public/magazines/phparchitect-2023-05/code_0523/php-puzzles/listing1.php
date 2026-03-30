<?php namespace OMerida\Maze;
class Renderer
{
  private \GdImage $image;
  private array $cells;
  private int $color1;
  private int $color2;
  private int $lineWidth;

  const WEST = 0x1;
  const EAST = 0x2;
  const SOUTH = 0x4;
  const NORTH = 0x8;

  public function __construct(
    private int $height,
    private int $width,
    private int $cellSize,
  ) { }

  public function setCells(array $cells): void {
    $this->cells = $cells;
  }

  public function draw(): bool
  {
    $padding = ceil($this->cellSize * 0.6);

    $this->image = imagecreatetruecolor(
      width: $this->width*$this->cellSize+$padding*2,
      height: $this->height*$this->cellSize+$padding*2,
    );

    $this->lineWidth = $this->cellSize * 0.1;

    // first call sets the background color
    $bg = imagecolorallocate(
      $this->image, 0xff, 0xff, 0xff
    );
    $this->color1 = imagecolorallocate(
      $this->image, 0x33, 0x33, 0x33
    );
    $this->color2 = imagecolorallocate(
      $this->image, 0x99, 0x99, 0x99
    );
    imagefill($this->image, 0, 0, $bg);
    imagesetthickness($this->image, $this->lineWidth);

    // starting top-left point
    $x = $y = $padding;
    foreach ($this->cells as $row) {
      foreach ($row as $cell) {
        $this->drawCell($cell, $x, $y);
        $x += $this->cellSize;
      }

      $x = $padding;
      $y += $this->cellSize;
    }
    return true;
  }