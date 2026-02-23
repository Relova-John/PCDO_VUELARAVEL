// src/types/cooperative.ts
export interface Cooperative {
  id: string;
  name: string;
  holder: string;
  type: string;
  member_count: number;
  has_ongoing_program: boolean;
  delinquent_history_count?: number;
  total_program_count?: number;
}

export interface Coop {
  id: string;
  name: string;
  holder: Holder;
  type: CoopType;
  member_count: number;
  has_ongoing_program: boolean;
  delinquent_history_count?: number;
  total_program_count?: number;
  details: Details;
  programs: Program[];
  checklists: Checklists[];
}

export interface Details {
  id: string;
  region_code: string;
  province_code: string;
  city_code: string;
  barangay_code: string;
  asset_size: string;
  coop_type: string;
  status_category: string;
  bond_of_membership: string;
  area_of_operation: string;
  citizenship: string;
  members_count: number;
  total_asset: number;
  net_surplus: number;
  email: string;
  number: string;
}

export interface Holder { id: number; name: string; }

export interface FileType {
  id: number;
  file_path: string;
  file_name: string;
  file_type: string;
}

export interface Member {
  id: number;
  coop_id: string;
  position: 'Chairman' | 'Treasurer' | 'Manager' | 'Member';
  contact: string;
  email?: string | null;
  first_name: string;
  middle_name: string;
  last_name: string;
  marital_status: string;
  street?: string | null;
  city?: string | null;
  telephone?: string | null;
  birthdate: string;
  age: number;
  sex: 'Male' | 'Female';
  citizenship: 'Filipino' | 'Others';
  birthplace: string;
  height?: number | null;
  weight?: number | null;
  religion: string;
  spouse_name?: string | null;
  spouse_occupation?: string | null;
  spouse_age?: number | null;
  father_name?: string | null;
  father_occupation?: string | null;
  father_age?: number | null;
  mother_name?: string | null;
  mother_occupation?: string | null;
  mother_age?: number | null;
  parent_address?: string | null;
  emergency_name: string;
  emergency_contact: string;

  dependent1_name?: string | null;
  dependent1_relationship?: string | null;
  dependent1_birthdate?: string | null;
  dependent1_age?: number | null;

  dependent2_name?: string | null;
  dependent2_relationship?: string | null;
  dependent2_birthdate?: string | null;
  dependent2_age?: number | null;

  elementary_start?: number | null;
  elementary_end?: number | null;
  elementary_name?: string | null;
  elementary_degree?: string | null;

  hs_start?: number | null;
  hs_end?: number | null;
  hs_name?: string | null;
  hs_degree?: string | null;

  college_start?: number | null;
  college_end?: number | null;
  college_name?: string | null;
  college_degree?: string | null;

  course_start?: number | null;
  course_end?: number | null;
  course_name?: string | null;
  course_degree?: string | null;

  others_start?: number | null;
  others_end?: number | null;
  others_name?: string | null;
  others_degree?: string | null;

  company1_start?: string | null;
  company1_end?: string | null;
  company1_name?: string | null;
  company1_position?: string | null;
  company1_rfl?: string | null;

  company2_start?: string | null;
  company2_end?: string | null;
  company2_name?: string | null;
  company2_position?: string | null;
  company2_rfl?: string | null;

  company3_start?: string | null;
  company3_end?: string | null;
  company3_name?: string | null;
  company3_position?: string | null;
  company3_rfl?: string | null;

  company4_start?: string | null;
  company4_end?: string | null;
  company4_name?: string | null;
  company4_position?: string | null;
  company4_rfl?: string | null;

  company5_start?: string | null;
  company5_end?: string | null;
  company5_name?: string | null;
  company5_position?: string | null;
  company5_rfl?: string | null;

  ref1_name?: string | null;
  ref1_company?: string | null;
  ref1_position?: string | null;
  ref1_contact?: string | null;

  ref2_name?: string | null;
  ref2_company?: string | null;
  ref2_position?: string | null;
  ref2_contact?: string | null;

  is_representative: boolean;
  active_year: number;
  files: FileType[];
}

export interface fullForm extends Details {
  name: string;
}

export interface Regions { code: string; name: string }
export interface Provinces { code: string; name: string; region_code: string }
export interface Cities { code: string; name: string; province_code: string, region_code: string }
export interface Barangays { code: string; name: string; city_code: string }

export interface Programs {
  id: number;
  name: string;
  details: string;
  archive: boolean;
  cooperatives_count: number;
  active_coop_count: number;
}

export interface Program {
  id: number;
  name: string;
  details: string;
  term_months: number;
  grace_period: number;
  min_amount: number;
  max_amount: number;
  penalty: number;
}

export interface Checklists {
  id: number;
  name: string;
}

export interface InventoryDetails {
  id: string;
  name: string;
  region_code: string;
  province_code: string;
  city_code: string;
  barangay_code: string;
  email: string;
  number: string;
  inventory: InventoryItem[];
}

export interface InventoryItem {
  id: number;
  name: string;
  category: string;
  guarantor_agency: string;
  location: string;
  value: number;
  quantity: number;
  status: string;
  acquired_date: string;
}

export enum CoopType {
  Credit = 'Credit', Consumer = 'Consumers', Producers = 'Producers', Marketing = 'Marketing', Service = 'Service',
  Multipurpose = 'Multipurpose', Advocacy = 'Advocacy', Agrarian_Reform = 'Agrarian Reform', Bank = 'Bank', Diary = 'Diary',
  Education = 'Education', Electric = 'Electric', Financial = 'Financial', Fishermen = 'Fishermen',
  Health_Services = 'Health Services', Housing = 'Housing', Insurance = 'Insurance', Water_Service = 'Water Service',
  Workers = 'Workers', Others = 'Others'
}

export enum AssetSize { Micro = 'Micro', Small = 'Small', Medium = 'Medium', Large = 'Large', Unclassified = 'Unclassified' }

export enum Status_Category { Reporting = 'Reporting', Non_Reporting = 'Non-Reporting', New = 'New' }

export enum BondOfMembership {
  Residential = 'Residential', Insitutional = 'Insitutional', Associational = 'Associational',
  Occupational = 'Occupational', Unspecified = 'Unspecified'
}

export enum AreaOfOperation { Municipal = 'Municipal', Provincial = 'Provincial' }

export enum Citizenship { Filipino = 'Filipino', Foreign = 'Foreign' }