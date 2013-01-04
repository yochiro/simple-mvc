#!/bin/bash

sed -f preprocess intro.tex | /home/ymikami/.cabal/bin/pandoc --reference-links --toc --columns=140 -p -S -f latex -t markdown --listings -R | sed -f postprocess > ../README.md
