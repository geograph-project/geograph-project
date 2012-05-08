/**
 * $Project: GeoGraph $
 * $Id$
 *
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2012 Toby Speight (http://www.geograph.org.uk/profile/608)
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

///////////////
// A Sphinx UDF to help return no more than N images per 'attribute' in a query
// http://sphinxsearch.com/docs/current.html#udf
///////////////

#include "sphinxudf.h"
#include <cstdio>
#include <map>
#include <string>

// Could use std::unordered_map in C++11, but let's be more portable:
typedef std::map<unsigned int,unsigned int> IntCount;
typedef std::map<std::string,unsigned int> StringCount;


#ifdef _MSC_VER
#define snprintf _snprintf
#define DLLEXPORT __declspec(dllexport)
#else
#define DLLEXPORT
#endif

extern "C" {
    DLLEXPORT int withinfirstx_init(SPH_UDF_INIT*, SPH_UDF_ARGS*, char*);
    DLLEXPORT void withinfirstx_deinit (SPH_UDF_INIT*);
    DLLEXPORT sphinx_int64_t withinfirstx(SPH_UDF_INIT*, SPH_UDF_ARGS*, char*);

    DLLEXPORT int withinfirstxmva32_init(SPH_UDF_INIT*, SPH_UDF_ARGS*, char*);
    DLLEXPORT void withinfirstxmva32_deinit (SPH_UDF_INIT*);
    DLLEXPORT sphinx_int64_t withinfirstxmva32(SPH_UDF_INIT*, SPH_UDF_ARGS*, char*);

    DLLEXPORT int withinfirstxstring_init(SPH_UDF_INIT*, SPH_UDF_ARGS*, char*);
    DLLEXPORT void withinfirstxstring_deinit (SPH_UDF_INIT*);
    DLLEXPORT sphinx_int64_t withinfirstxstring(SPH_UDF_INIT*, SPH_UDF_ARGS*, char*);
}

//////////////////////////////////////////////////////
// for uint (32)

/// UDF initialization
/// gets called on every query, when query begins
/// args are filled with values for a particular query
DLLEXPORT int withinfirstx_init(SPH_UDF_INIT *init,
                                SPH_UDF_ARGS *args,
                                char *error_message)
{
    // check argument count
    if (args->arg_count != 2) {
        snprintf(error_message, SPH_UDF_ERROR_LEN, "withinFirstX() takes 2 arguments");
        return 1;
    }

    // check argument types
    if (args->arg_types[0] != SPH_UDF_TYPE_UINT32) {
        snprintf(error_message, SPH_UDF_ERROR_LEN, "withinFirstX() requires 1st argument to be uint");
        return 1;
    }
    if (args->arg_types[1] != SPH_UDF_TYPE_UINT32) {
        snprintf(error_message, SPH_UDF_ERROR_LEN, "withinFirstX() requires 2nd argument to be uint");
        return 1;
    }

    init->func_data = new IntCount();

    // all done
    return 0;
}


/// UDF deinitialization
/// gets called on every query, when query ends
DLLEXPORT void withinfirstx_deinit(SPH_UDF_INIT *init)
{
    // deallocate storage
    if (init->func_data) {
        IntCount *m = static_cast<IntCount*>(init->func_data);
        delete m;
        init->func_data = NULL;
    }
}


/// UDF implementation
/// gets called for every row, unless optimized away
DLLEXPORT sphinx_int64_t withinfirstx(SPH_UDF_INIT *init, SPH_UDF_ARGS *args, char *)
{
    unsigned int unique = *(unsigned int*)args->arg_values[0];
    unsigned int limit  = *(unsigned int*)args->arg_values[1];
    IntCount *m = static_cast<IntCount*>(init->func_data);

    return ++(*m)[unique] <= limit;
}

//////////////////////////////////////////////////////
// for mva32

/// UDF initialization
/// gets called on every query, when query begins
/// args are filled with values for a particular query
DLLEXPORT int withinfirstxmva32_init(SPH_UDF_INIT *init,
                                SPH_UDF_ARGS *args,
                                char *error_message)
{
    // check argument count
    if (args->arg_count != 2) {
        snprintf(error_message, SPH_UDF_ERROR_LEN, "withinFirstX() takes 2 arguments");
        return 1;
    }

    // check argument types
    if (args->arg_types[0] != SPH_UDF_TYPE_UINT32SET) {
        snprintf(error_message, SPH_UDF_ERROR_LEN, "withinFirstX() requires 1st argument to be 32bit mva");
        return 1;
    }
    if (args->arg_types[1] != SPH_UDF_TYPE_UINT32) {
        snprintf(error_message, SPH_UDF_ERROR_LEN, "withinFirstX() requires 2nd argument to be uint");
        return 1;
    }

    init->func_data = new IntCount();

    // all done
    return 0;
}


/// UDF deinitialization
/// gets called on every query, when query ends
DLLEXPORT void withinfirstxmva32_deinit(SPH_UDF_INIT *init)
{
    // deallocate storage
    if (init->func_data) {
        IntCount *m = static_cast<IntCount*>(init->func_data);
        delete m;
        init->func_data = NULL;
    }
}


/// UDF implementation
/// gets called for every row, unless optimized away
DLLEXPORT sphinx_int64_t withinfirstxmva32(SPH_UDF_INIT *init, SPH_UDF_ARGS *args, char *)
{
    unsigned int * mva = (unsigned int *) args->arg_values[0];

    //deal with being passed a empty array
    if ( !mva )
        return 1;


    IntCount *m = static_cast<IntCount*>(init->func_data);
    unsigned int limit  = *(unsigned int*)args->arg_values[1];

    // Both MVA32 and MVA64 are stored as dword (unsigned 32-bit) arrays.
    // The first dword stores the array length (always in dwords too), and
    // the next ones store the values. In pseudocode:
    //
    // unsigned int num_dwords
    // unsigned int data [ num_dwords ]
    //
    // With MVA32, this lets you access the values pretty naturally.

    int i, n, ok;

    ok = 1;
    n = *mva++;
    for ( i=0; i<n; i++ ) {
        //we have to go though the whole loop, so all the counters are incremented - no ending early
        if (++(*m)[*mva++] > limit)
             ok = 0;
    }
    return ok;
}

//////////////////////////////////////////////////////
// for strings

/// UDF initialization
/// gets called on every query, when query begins
/// args are filled with values for a particular query
DLLEXPORT int withinfirstxstring_init(SPH_UDF_INIT *init,
                                SPH_UDF_ARGS *args,
                                char *error_message)
{
    // check argument count
    if (args->arg_count != 2) {
        snprintf(error_message, SPH_UDF_ERROR_LEN, "withinFirstXString() takes 2 arguments");
        return 1;
    }

    // check argument types
    if (args->arg_types[0] != SPH_UDF_TYPE_STRING) {
        snprintf(error_message, SPH_UDF_ERROR_LEN, "withinFirstXString() requires 1st argument to be string");
        return 1;
    }
    if (args->arg_types[1] != SPH_UDF_TYPE_UINT32) {
        snprintf(error_message, SPH_UDF_ERROR_LEN, "withinFirstXString() requires 2nd argument to be uint");
        return 1;
    }

    init->func_data = new StringCount();

    // all done
    return 0;
}


/// UDF deinitialization
/// gets called on every query, when query ends
DLLEXPORT void withinfirstxstring_deinit(SPH_UDF_INIT *init)
{
    // deallocate storage
    if (init->func_data) {
        StringCount *m = static_cast<StringCount*>(init->func_data);
        delete m;
        init->func_data = NULL;
    }
}


/// UDF implementation
/// gets called for every row, unless optimized away
DLLEXPORT sphinx_int64_t withinfirstxstring(SPH_UDF_INIT *init, SPH_UDF_ARGS *args, char *)
{
    const std::string unique(args->arg_values[0], args->str_lengths[0]);
    unsigned int limit  = *(unsigned int*)args->arg_values[1];
    StringCount *m = static_cast<StringCount*>(init->func_data);

    return ++(*m)[unique] <= limit;
}

