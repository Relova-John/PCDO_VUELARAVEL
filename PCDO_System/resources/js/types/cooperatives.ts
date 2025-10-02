// src/types/cooperative.ts
export interface Cooperative {
  id: string;
  name: string;
  holder: string;
  type: string;
  member_count: number;
  has_ongoing_program: boolean;
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
}

export interface Holder { id: number; name: string; }

export type SingleChar = '' | `${string}` & { length: 1 };

export interface FileType {
  id: number;
  file_path: string;
  file_name: string;
  file_type: string;
}
export interface Member {
  id: number;
  coop_id: string;
  position: string;
  active_year: number;
  first_name: string;
  middle_initial: SingleChar;
  last_name: string;
  suffix?: string;
  is_representative: boolean;
  files: FileType[];
}

export interface fullForm extends Details {
  name: string;
}

export interface Regions { code: string; name: string }
export interface Provinces { code: string; name: string; region_code: string }
export interface Cities { code: string; name: string; province_code: string, region_code: string }
export interface Barangays { code: string; name: string; city_code: string }

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
