#!/bin/sh
#
# $Id: rw.sh,v 1.1.1.1 2008/08/01 07:56:20 root Exp $

mount -u -o rw,sync,noatime /dev/wd0d /conf
