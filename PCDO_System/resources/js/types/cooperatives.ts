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
  region_id: number;
  province_id: number;
  municipality_id: number;
  barangay_id: number;
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

export interface fullForm extends Details {
  name: string;
}

export interface Regions { id: number; name: string }
export interface Provinces { id: number; name: string; region_id: number }
export interface Municipalities { id: number; name: string; province_id: number }
export interface Barangays { id: number; name: string; municipality_id: number }

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
