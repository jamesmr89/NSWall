#!/bin/sh

# Create dir if not there
mkdir -p obj

# Create a templist
cat list > list.temp
# Include custom list if exist
if [ -r list.custom ]; then
        cat list.custom >> list.temp
fi

# Modify list.temp to use fstab.initial.iso and
# add mount_cd9660.
cat list.temp | sed 's/fstab.initial/fstab.initial.iso/' |  sed '/mount_msdos/a\
COPY    ${DESTDIR}/sbin/mount_cd9660            sbin/mount_cd9660\
' > list.temp2
rm list.temp
mv list.temp2 list.temp

# Cleanup just in case the previous build failed
umount /mnt
vnconfig -u vnd0
make KCONF=${KERNEL} clean

# Make kernel
make termdefs bsd KCONF=${KERNEL} LIST=/list.temp NBLKS=${NBLKS} DISKPROTO=/disktabs/${DISKTAB} $2 $3 $4

exit

