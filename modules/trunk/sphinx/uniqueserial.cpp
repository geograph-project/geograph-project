/**
 * $Project: GeoGraph $
 * $Id$
 *
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2012 Barry Hunter (geo@barryhunter.co.uk)
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
// A Sphinx UDF to return a unique serial number based on a key
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

    DLLEXPORT int uniqueserial_ver();

    DLLEXPORT int uniqueserial_init(SPH_UDF_INIT*, SPH_UDF_ARGS*, char*);
    DLLEXPORT void uniqueserial_deinit (SPH_UDF_INIT*);
    DLLEXPORT sphinx_int64_t uniqueserial(SPH_UDF_INIT*, SPH_UDF_ARGS*, char*);

    DLLEXPORT int uniqueserialstring_init(SPH_UDF_INIT*, SPH_UDF_ARGS*, char*);
    DLLEXPORT void uniqueserialstring_deinit (SPH_UDF_INIT*);
    DLLEXPORT sphinx_int64_t uniqueserialstring(SPH_UDF_INIT*, SPH_UDF_ARGS*, char*);
}


/// UDF version control
/// gets called once when the library is loaded
DLLEXPORT int uniqueserial_ver()
{
        return SPH_UDF_VERSION;
}

//////////////////////////////////////////////////////
// for uint (32)

/// UDF initialization
/// gets called on every query, when query begins
/// args are filled with values for a particular query
DLLEXPORT int uniqueserial_init(SPH_UDF_INIT *init,
                                SPH_UDF_ARGS *args,
                                char *error_message)
{
    // check argument count
    if (args->arg_count != 1) {
        snprintf(error_message, SPH_UDF_ERROR_LEN, "uniqueserial() takes 1 argument");
        return 1;
    }

    // check argument types
    if (args->arg_types[0] != SPH_UDF_TYPE_UINT32) {
        snprintf(error_message, SPH_UDF_ERROR_LEN, "uniqueserial() requires 1st argument to be uint");
        return 1;
    }

    init->func_data = new IntCount();

    // all done
    return 0;
}


/// UDF deinitialization
/// gets called on every query, when query ends
DLLEXPORT void uniqueserial_deinit(SPH_UDF_INIT *init)
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
DLLEXPORT sphinx_int64_t uniqueserial(SPH_UDF_INIT *init, SPH_UDF_ARGS *args, char *)
{
    unsigned int unique = *(unsigned int*)args->arg_values[0];
    IntCount *m = static_cast<IntCount*>(init->func_data);

    return ++(*m)[unique];
}

//////////////////////////////////////////////////////
// for strings

/// UDF initialization
/// gets called on every query, when query begins
/// args are filled with values for a particular query
DLLEXPORT int uniqueserialstring_init(SPH_UDF_INIT *init,
                                SPH_UDF_ARGS *args,
                                char *error_message)
{
    // check argument count
    if (args->arg_count != 1) {
        snprintf(error_message, SPH_UDF_ERROR_LEN, "uniqueserialString() takes 1 argument");
        return 1;
    }

    // check argument types
    if (args->arg_types[0] != SPH_UDF_TYPE_STRING) {
        snprintf(error_message, SPH_UDF_ERROR_LEN, "uniqueserialString() requires 1st argument to be string");
        return 1;
    }

    init->func_data = new StringCount();

    // all done
    return 0;
}


/// UDF deinitialization
/// gets called on every query, when query ends
DLLEXPORT void uniqueserialstring_deinit(SPH_UDF_INIT *init)
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
DLLEXPORT sphinx_int64_t uniqueserialstring(SPH_UDF_INIT *init, SPH_UDF_ARGS *args, char *)
{
    const std::string unique(args->arg_values[0], args->str_lengths[0]);
    StringCount *m = static_cast<StringCount*>(init->func_data);

    return ++(*m)[unique];
}

