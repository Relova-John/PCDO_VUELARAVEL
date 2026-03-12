export interface Regions { code: string; name: string }
export interface Provinces { code: string; name: string; region_code: string }
export interface Cities { code: string; name: string; province_code: string, region_code: string }
export interface Barangays { code: string; name: string; city_code: string }