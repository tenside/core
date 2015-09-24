#!/bin/bash
for i in `find . -maxdepth 1 ! -path . -type d`; do (cd $i; rm ../$i.zip; zip ../$i.zip -r --symlinks .) done;
