#!/bin/bash
for i in *.zip; do (unzip -o $i -d `basename $i .zip` ) done;
