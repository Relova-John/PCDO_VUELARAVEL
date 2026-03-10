export interface Regions { code: string; name: string }
export interface Provinces { code: string; name: string; region_code: string }
export interface Cities { code: string; name: string; province_code: string, region_code: string }
export interface Barangays { code: string; name: string; city_code: string }

export interface CoopDetails {
  name: string;
  region_code: string;
  province_code: string;
  city_code: string;
  barangay_code: string;
  email: string;
  number: string;
  inventoryItem: InventoryItem[];
}

export interface InventoryItem {
  id: number;
  name: string;
  category: string;
  guarantor_agency: string;
  location: string;
  value: number;
  quantity: number;
  status: number | null;
  acquired_date: string;
}
