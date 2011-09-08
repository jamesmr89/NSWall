/* $nsh: ip.h,v 1.3 2003/02/18 09:29:46 chris Exp $ */
/*
 * A clean way to represent IPv4/IPv6 addresses for routines which purport to
 * handle either, inspiration from mrtd
 */
#ifndef _IP_T_
#define _IP_T_
#endif
typedef struct _ip_t {
	u_short family;		/* AF_INET | AF_INET6 */
	int bitlen;		/* bits */
	int ref_count;          /* reference count */
	union {
		struct in_addr sin;
		struct in6_addr sin6;
	} addr;
} ip_t;

