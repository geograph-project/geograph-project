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

// Could use std::unordered_map in C++11, but let's be more portable:
typedef std::map<unsigned int,unsigned int> UserMap;


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
}

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

    init->func_data = new UserMap();

    // all done
    return 0;
}


/// UDF deinitialization
/// gets called on every query, when query ends
DLLEXPORT void withinfirstx_deinit(SPH_UDF_INIT *init)
{
    // deallocate storage
    if (init->func_data) {
        UserMap *m = static_cast<UserMap*>(init->func_data);
        delete m;
        init->func_data = NULL;
    }
}


/// UDF implementation
/// gets called for every row, unless optimized away
DLLEXPORT sphinx_int64_t withinfirstx(SPH_UDF_INIT *init, SPH_UDF_ARGS *args, char *)
{
    UserMap *m = static_cast<UserMap*>(init->func_data);
    unsigned int unique = *(unsigned int*)args->arg_values[0];
    unsigned int limit  = *(unsigned int*)args->arg_values[1];

    return ++(*m)[unique] <= limit;
}
